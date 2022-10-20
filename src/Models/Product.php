<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use WeDevelop\Akeneo\Pages\ProductPage;

/**
 * @method AttributeValues()
 * @method Associations()
 */
class Product extends DataObject implements AkeneoImportInterface
{
    /** @config */
    private static string $table_name = 'Akeneo_Product';

    /** @config */
    private static string $singular_name = 'Product';

    /** @config */
    private static string $plural_name = 'Products';

    /** @config */
    private static array $db = [
        'SKU' => 'Varchar(255)',
        'Enabled' => 'Boolean',
        'Updated' => 'Boolean',
    ];

    /** @config */
    private static array $has_one = [
        'Family' => Family::class,
        'Parent' => ProductModel::class,
    ];

    /** @config */
    private static array $has_many = [
        'AttributeValues' => ProductAttributeValue::class,
        'Associations' => ProductAssociation::class . '.Product',
    ];

    /** @config */
    private static array $many_many = [
        'Categories' => ProductCategory::class,
    ];

    /** @config */
    private static array $belongs_to = [
        'ProductPage' => ProductPage::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'ID',
        'SKU',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Updated');

        foreach ($fields->findOrMakeTab('Root.Main')->Fields() as $field) {
            $fields->makeFieldReadonly($field);
        }

        $fields->addFieldToTab('Root.AttributeValues', new GridField('AttributeValues', 'AttributeValues', $this->AttributeValues(), GridFieldConfig_RecordViewer::create()));

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

    public function populateAkeneoData(array $akeneoProduct, string $locale, array $relatedObjectIds = []): void
    {
        $this->SKU = $akeneoProduct['identifier'];
        $this->Enabled = $akeneoProduct['enabled'];
        $this->Updated = true;

        foreach ($relatedObjectIds as $field => $value) {
            $this->{$field} = $value;
        }

        foreach ($akeneoProduct['categories'] as $categoryCode) {
            $this->Categories()->add(ProductCategory::get()->find('Code', $categoryCode));
        }
    }

    public function getImportOutput(): string
    {
        return $this->singular_name() . ': ' . $this->ID . ' - ' . $this->SKU;
    }

    public static function getIdentifierField(): string
    {
        return 'SKU';
    }
}
