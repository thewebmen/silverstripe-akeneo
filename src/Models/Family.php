<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Member;

/**
 * @method HasManyList LabelTranslations()
 */
class Family extends AbstractAkeneoTranslateable implements AkeneoImportInterface
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
        'LabelTranslations' => LabelTranslation::class
    ];

    /** @config */
    private static array $many_many = [
        'Attributes' => ProductAttribute::class,
    ];

    /** @config */
    private static array $cascade_deletes = [
        'Variants',
    ];

    public function populateAkeneoData(array $akeneoItem, array $relatedObjectIds = []): void
    {
        $this->Code = $akeneoItem['code'];
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

        $this->updateLabels($akeneoItem);
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
