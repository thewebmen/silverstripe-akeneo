<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Security\Member;

class ProductAttributeGroup extends AbstractAkeneoTranslateable implements AkeneoImportInterface
{
    /** @config */
    private static string $table_name = 'Akeneo_ProductAttributeGroup';

    /** @config */
    private static string $singular_name = 'Attribute Group';

    /** @config */
    private static string $plural_name = 'Attributes Groups';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(255)',
        'Name' => 'Varchar(255)',
        'Sort' => 'Int',
        'Updated' => 'Boolean',
    ];

    /** @config */
    private static array $has_many = [
        'Attributes' => ProductAttribute::class,
        'LabelTranslations' => LabelTranslation::class
    ];

    /** @config */
    private static array $summary_fields = [
        'Code' => 'Code',
        'Name' => 'Name',
    ];

    /** @config */
    private static string $default_sort = '"Sort"';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Updated');
        $fields->removeByName('Sort');

        foreach ($fields->findOrMakeTab('Root.Main')->Fields() as $field) {
            $fields->makeFieldReadonly($field);
        }

        if ($this->Attributes()->count() > 0) {
            $fields->addFieldToTab('Root.Options', new GridField('Attributes', 'Attributes', $this->Attributes(), GridFieldConfig_RecordEditor::create()));
        }

        return $fields;
    }

    /**
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        return false;
    }

    /**
     * @param Member $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * @param Member $member
     * @param array $context
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function populateAkeneoData(array $akeneoItem, array $relatedObjectIds): void
    {
        $this->Code = $akeneoItem['code'];
        $this->Updated = true;
        $this->Sort = $akeneoItem['sort_order'];

        foreach ($relatedObjectIds as $field => $value) {
            $this->{$field} = $value;
        }

        $this->updateLabels($akeneoItem);
    }

    public function getImportOutput(): string
    {
        return $this->singular_name() . ': ' . $this->ID . ' - ' . $this->Name;
    }

    public static function getIdentifierField(): string
    {
        return 'Code';
    }
}
