<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;

/**
 * @method ProductAttribute Attribute()
 */
class ProductAttributeValue extends DataObject
{
    public const PIM_CATALOG_DATE = 'pim_catalog_date';
    public const PIM_CATALOG_FILE_TYPE = 'pim_catalog_file';
    public const PIM_CATALOG_IMAGE_TYPE = 'pim_catalog_image';
    public const PIM_CATALOG_MULTISELECT_TYPE = 'pim_catalog_multiselect';
    public const PIM_CATALOG_PRICE_COLLECTION = 'pim_catalog_price_collection';
    public const PIM_CATALOG_SIMPLESELECT_TYPE = 'pim_catalog_simpleselect';
    public const PIM_CATALOG_TEXTAREA_TYPE = 'pim_catalog_textarea';

    /** @config */
    private static string $table_name = 'Akeneo_ProductAttributeValue';

    /** @config */
    private static string $singular_name = 'Attribute value';

    /** @config */
    private static string $plural_name = 'Attribute values';

    /** @config */
    private static array $db = [
        'Value' => 'Varchar(255)',
        'TextValue' => 'Text',
    ];

    /** @config */
    private static array $has_one = [
        'ProductModel' => ProductModel::class,
        'Product' => Product::class,
        'Attribute' => ProductAttribute::class,
        'Locale' => Locale::class
    ];

    /** @config */
    private static array $summary_fields = [
        'LocalisedAttributeName' => 'Attribute',
        'Value' => 'Value',
        'Attribute.Type' => 'Type',
    ];

    public function getValue()
    {
        $value = $this->getField('Value');
        $attribute = $this->Attribute();

        switch ($attribute->Type) {
            case self::PIM_CATALOG_SIMPLESELECT_TYPE:
                return $attribute->Options()->filter('Code', $value)->first()->Name;
            case self::PIM_CATALOG_MULTISELECT_TYPE:
                $values = json_decode($value);
                $attributeNames = array_map(static function(ProductAttributeOption $option) {
                    return $option->getName();
                }, $attribute->Options()->filter('Code', $values)->toArray());

                return implode(', ', $attributeNames);
            case self::PIM_CATALOG_PRICE_COLLECTION:
                $price = json_decode($value, true);
                return $price[0]['currency'] . ' ' . $price[0]['amount'];
            case self::PIM_CATALOG_FILE_TYPE:
            case self::PIM_CATALOG_IMAGE_TYPE:
                return ProductMediaFile::get()->find('Code', $value)?->getAttributeValue();
            case self::PIM_CATALOG_DATE:
                return DBDatetime::create()->setValue($value)->Nice();
            case self::PIM_CATALOG_TEXTAREA_TYPE:
                return DBField::create_field('HTMLText', $this->getField('TextValue'));
            default:
                return $value;
        }
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();

        $attribute = $this->Attribute();

        if ($attribute->Type === self::PIM_CATALOG_FILE_TYPE) {
            $file = File::get()->byID($this->Value);
            $file?->delete();
        } elseif ($attribute->Type === self::PIM_CATALOG_IMAGE_TYPE) {
            $image = Image::get()->byID($this->Value);
            $image?->delete();
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
     * @array $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public static function getIdentifierField(): string
    {
        return 'Code';
    }

    public function getLocalisedAttributeName(): string
    {
        if ($this->Locale()->exists()) {
            return '[' . $this->Locale()->Code . '] ' . $this->Attribute()->Name;
        }

        return $this->Attribute()->Name;
    }
}
