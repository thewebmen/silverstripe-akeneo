<?php

namespace WeDevelop\Akeneo\Admins;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Akeneo\Imports\AkeneoImport;
use WeDevelop\Akeneo\Models\Family;
use WeDevelop\Akeneo\Models\Product;
use WeDevelop\Akeneo\Models\ProductAttribute;
use WeDevelop\Akeneo\Models\ProductCategory;
use WeDevelop\Akeneo\Models\ProductModel;

class AkeneoAdmin extends ModelAdmin
{
    /** @config */
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
    private static string $menu_icon = 'wedevelop/silverstripe-akeneo:images/akeneo.png';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        if ($this->modelClass === ProductCategory::class && $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
            if ($gridField instanceof GridField) {
                $gridField->getConfig()->addComponent(new GridFieldOrderableRows('Sort'));
            }
        }

        $form->Actions()->push(FormAction::create('doSync', 'Sync'));

        return $form;
    }

    public function doSync(): void
    {
        /** @var  AkeneoImport $import */
        $import = Injector::inst()->get('AkeneoImport');
        $import->run([]);

        Controller::curr()->getResponse()->addHeader('X-Status', 'Synced');
    }
}
