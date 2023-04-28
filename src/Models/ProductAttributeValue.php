<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use WeDevelop\Akeneo\Util\AttributeParser;

/**
 * @method ProductAttribute Attribute()
 */
class ProductAttributeValue extends DataObject
{
    public const PIM_CATALOG_DATE = 'pim_catalog_date';
    public const PIM_CATALOG_FILE_TYPE = 'pim_catalog_file';
    public const PIM_CATALOG_IMAGE_TYPE = 'pim_catalog_image';
    public const PIM_CATALOG_METRIC_TYPE = 'pim_catalog_metric';
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
        'Locale' => Locale::class,
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

        return match ($attribute->Type) {
            self::PIM_CATALOG_SIMPLESELECT_TYPE => $attribute->Options()->filter('Code', $value)->first()->Name,
            self::PIM_CATALOG_MULTISELECT_TYPE => DBField::create_field('HTMLText', AttributeParser::MultiSelectParser($this)),
            self::PIM_CATALOG_PRICE_COLLECTION => DBField::create_field('HTMLText', AttributeParser::PriceCollectionParser($this)),
            self::PIM_CATALOG_FILE_TYPE, self::PIM_CATALOG_IMAGE_TYPE => ProductMediaFile::get()->find('Code', $value)?->getAttributeValue(),
            self::PIM_CATALOG_DATE => DBDatetime::create()->setValue($value)->Nice(),
            self::PIM_CATALOG_TEXTAREA_TYPE => DBField::create_field('HTMLText', $this->getField('TextValue')),
            self::PIM_CATALOG_METRIC_TYPE => DBField::create_field('HTMLText', AttributeParser::MetricTypeParser($this)),
            default => strval($value),
        };
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
