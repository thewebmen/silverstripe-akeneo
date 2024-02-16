<?php

namespace WeDevelop\Akeneo\Admins;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Akeneo\Models\Display\DisplayGroup;
use WeDevelop\Akeneo\Models\Family;
use WeDevelop\Akeneo\Models\Product;
use WeDevelop\Akeneo\Models\ProductAttribute;
use WeDevelop\Akeneo\Models\ProductCategory;
use WeDevelop\Akeneo\Models\ProductModel;
use WeDevelop\Config\AkeneoConfig;

class AkeneoAdmin extends ModelAdmin
{
    /**
     * @config
     * @var array<class-string>
     */
    private static array $managed_models = [
        Product::class,
        ProductModel::class,
        ProductCategory::class,
        ProductAttribute::class,
        Family::class,
    ];

    /** @config */
    private static string $url_segment = 'akeneo';

    /** @config */
    private static string $menu_title = 'Akeneo';

    /** @config */
    private static string $menu_icon = 'wedevelopnl/silverstripe-akeneo:images/akeneo.png';

    public function getEditForm($id = null, $fields = null): Form
    {
        $form = parent::getEditForm($id, $fields);

        if ($this->modelClass === ProductCategory::class && $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
            if ($gridField instanceof GridField) {
                $gridField->getConfig()->addComponent(new GridFieldOrderableRows('Sort'));
            }
        }

        if ($this->modelClass === DisplayGroup::class && $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
            if ($gridField instanceof GridField) {
                $originalField = clone $gridField;

                /** @var DataList $gridFieldList */
                $gridFieldList = $gridField->getList();
                /** @var DataList $originalFieldList */
                $originalFieldList = $originalField->getList();

                $originalField->setList(
                    $originalFieldList->filter([
                        'IsRootGroup' => 0,
                    ]),
                );

                $gridField->setName('RootDisplayGroups');
                $gridField->setList(
                    $gridFieldList->filter([
                        'IsRootGroup' => 1,
                    ]),
                );
            }

            $fields = $form->Fields();

            $fields->push(TabSet::create('Root', 'Root'));
            $fields->addFieldsToTab('Root.Root Groups', [
                HeaderField::create('RootHeader', 'Root groups have no parent group associated with them'),
                $gridField,
            ]);

            if (!empty($originalField)) {
                $fields->addFieldsToTab('Root.Sub Groups', [
                    HeaderField::create('SubHeader', 'Sub groups are part of a group chain, and therefore have a parent group defined somewhere.'),
                    $originalField,
                ]);
            }
        }

        $importRunning = self::isImportRunning();
        $form->Actions()->push(
            FormAction::create('doSync', self::isImportRunning() ? 'Import running' : 'Sync with Akeneo')
                ->setUseButtonTag(true)
                ->addExtraClass('btn btn-primary mt-2 mb-2 icon font-icon-sync')
                ->setDisabled($importRunning)
        );

        return $form;
    }

    public function doSync(): void
    {
        try {
            $importMessage = self::asyncImport();
        } catch (\Exception $e) {
            $importMessage = $e->getMessage();
        }

        Controller::curr()->getResponse()->addHeader('X-Status', $importMessage);
    }

    private static function isImportRunning(): bool
    {
        exec('ps | grep AkeneoImportTask', $psOutput);

        return (!empty($psOutput) && count($psOutput) > 2);
    }

    public static function asyncImport(): string
    {
        if (self::isImportRunning()) {
            throw new \Exception('An import is still running.');
        }

        $command = sprintf('php%s ../vendor/silverstripe/framework/cli-script.php dev/tasks/AkeneoImportTask > /dev/null &', self::currentPHPversion());
        exec($command);

        return 'Import started';
    }

    public function getCMSEditLink(DataObject $object, string $subTab = ''): string
    {
        $sanitisedClassname = $this->sanitiseClassName($object::class);

        $editFormField = 'EditForm/field/';

        if ($subTab) {
            $editFormField .= $subTab . '/';
        }

        return Controller::join_links(
            $this->Link($sanitisedClassname),
            $editFormField,
            $sanitisedClassname,
            'item',
            $object->ID
        );
    }

    public function getManagedModels(): array
    {
        $managed = parent::getManagedModels();

        if (AkeneoConfig::getEnableDisplayGroups()) {
            $managed[DisplayGroup::class] = [
                'title' => DisplayGroup::singleton()->plural_name(),
                'dataClass' => DisplayGroup::class,
            ];
        }

        return $managed;
    }

    private static function currentPHPversion(): string
    {
        $majorityParts = explode('.', phpversion());

        return implode('.', array_slice($majorityParts, 0, 2));
    }
}
