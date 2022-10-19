<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

class Family extends DataObject implements AkeneoImportInterface
{
    /** @config */
    private static string $table_name = 'Akeneo_Family';

    /** @config */
    private static string $singular_name = 'Family';

    /** @config */
    private static string $plural_name = 'Families';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(255)',
        'Name' => 'Varchar(255)',
        'Updated' => 'Boolean',
    ];

    /** @config */
    private static array $has_one = [
        'AttributeAsLabel' => ProductAttribute::class,
        'AttributeAsImage' => ProductAttribute::class,
    ];

    /** @config */
    private static array $has_many = [
        'Variants' => FamilyVariant::class,
    ];

    /** @config */
    private static array $many_many = [
        'Attributes' => ProductAttribute::class,
    ];

    /** @config */
    private static array $cascade_deletes = [
        'Variants',
    ];

    public function populateAkeneoData(array $akeneoItem, string $locale, array $relatedObjectIds = []): void
    {
        $this->Code = $akeneoItem['code'];
        $this->Name = $akeneoItem['labels'][$locale] ?? $akeneoItem['code'];
        $this->Updated = true;

        foreach ($akeneoItem['attributes'] as $attributeCode) {
            $this->Attributes()->add(ProductAttribute::get()->find('code', $attributeCode));
        }

        $attributeAsLabelCode = $akeneoItem['attribute_as_label'];
        $attributeAsImageCode = $akeneoItem['attribute_as_image'];
        $attributeAsLabel = ProductAttribute::get()->find('code', $attributeAsLabelCode);
        $attributeAsImage = ProductAttribute::get()->find('code', $attributeAsImageCode);
        $this->AttributeAsLabelID = $attributeAsLabel?->ID;
        $this->AttributeAsImageID = $attributeAsImage?->ID;

        foreach ($relatedObjectIds as $field => $value) {
            $this->{$field} = $value;
        }
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

    public function getImportOutput(): string
    {
        return $this->singular_name() . ': ' . $this->ID . ' - ' . $this->Name;
    }

    public static function getIdentifierField(): string
    {
        return 'Code';
    }
}
