<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * @method Associations()
 */
class ProductModel extends DataObject implements AkeneoImportInterface
{
    /** @config */
    private static string $table_name = 'Akeneo_ProductModel';

    /** @config */
    private static string $singular_name = 'Product Model';

    /** @config */
    private static string $plural_name = 'Product Models';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(255)',
        'Updated' => 'Boolean',
    ];

    /** @config */
    private static array $has_one = [
        'Parent' => self::class,
        'FamilyVariant' => FamilyVariant::class,
    ];

    /** @config */
    private static array $has_many = [
        'AttributeValues' => ProductAttributeValue::class,
        'Associations' => ProductAssociation::class . '.ProductModel',
    ];

    /** @config */
    private static array $many_many = [
        'Categories' => ProductCategory::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'Code' => 'Code',
        'Parent.Code' => 'Parent',
        'FamilyVariant.Family.Name' => 'Family',
        'FamilyVariant.Name' => 'Variant',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Updated');

        foreach ($fields->findOrMakeTab('Root.Main')->Fields() as $field) {
            $fields->makeFieldReadonly($field);
        }

        return $fields;
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
     *
     * @return false
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function populateAkeneoData(array $akeneoItem, array $relatedObjectIds = []): void
    {
        $this->Code = $akeneoItem['code'];
        $this->Updated = true;

        foreach ($relatedObjectIds as $field => $value) {
            $this->{$field} = $value;
        }

        foreach ($akeneoItem['categories'] as $categoryCode) {
            $this->Categories()->add(ProductCategory::get()->find('Code', $categoryCode));
        }
    }


    public function getImportOutput(): string
    {
        return $this->singular_name() . ': ' . $this->ID . ' - ' . $this->Code;
    }

    public static function getIdentifierField(): string
    {
        return 'Code';
    }

    /**
     * @return DataList<Product>
     */
    public function getProducts(): DataList
    {
        return Product::get()->filter('ProductModelID', $this->ID);
    }
}
