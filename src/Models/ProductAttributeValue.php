<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use WeDevelop\Akeneo\Enums\ProductAttributeType;
use WeDevelop\Akeneo\Util\AttributeParser;

/**
 * @method ProductAttribute Attribute()
 */
class ProductAttributeValue extends DataObject
{
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
        if (($value = $this->getField('Value')) === null) {
            return null;
        }

        $attribute = $this->Attribute();

        return match (ProductAttributeType::tryFrom($attribute->Type)) {
            ProductAttributeType::BOOLEAN => (bool)$value ? _t(self::class.'.Yes', 'Yes') : _t(self::class.'.No', 'No'),
            ProductAttributeType::DATE => DBDatetime::create()->setValue($value)->Nice(),
            ProductAttributeType::FILE, ProductAttributeType::IMAGE => ProductMediaFile::get()->find('Code', $value)?->getAttributeValue(),
            ProductAttributeType::METRIC => DBField::create_field('HTMLText', AttributeParser::MetricTypeParser($this)),
            ProductAttributeType::MULTISELECT => DBField::create_field('HTMLText', AttributeParser::MultiSelectParser($this)),
            ProductAttributeType::PRICE_COLLECTION => DBField::create_field('HTMLText', AttributeParser::PriceCollectionParser($this)),
            ProductAttributeType::SIMPLESELECT => $attribute->Options()->filter('Code', $value)->first()->Name,
            ProductAttributeType::TEXT => DBField::create_field('HTMLText', (string)$value),
            ProductAttributeType::TEXTAREA => DBField::create_field('HTMLText', nl2br($this->getField('TextValue') ?? $value)),
            default => (string)$value,
        };
    }

    protected function onAfterDelete()
    {
        parent::onAfterDelete();

        $productAttributeFile = match (ProductAttributeType::tryFrom($this->Attribute()->Type)) {
            ProductAttributeType::FILE => File::get()->byID($this->Value),
            ProductAttributeType::IMAGE => Image::get()->byID($this->Value),
            default => null,
        };

        if ($productAttributeFile instanceof DataObject) {
            $productAttributeFile->delete();
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
