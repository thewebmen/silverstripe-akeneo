<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Member;
use SilverStripe\View\ArrayData;
use WeDevelop\Akeneo\Pages\ProductPage;
use SilverStripe\ORM\ArrayList;

/**
 * @method HasManyList<ProductAttributeValue> AttributeValues()
 * @method HasManyList<ProductAssociation> Associations()
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
        'SKU',
        'Family.Name' => 'Family',
        'LabelFromAttribute' => 'Label'
    ];

    /** @config */
    private static array $searchable_fields = [
        'ID',
        'SKU'
    ];

    public function getLocaleFromRequest(): string
    {
        $controller = Controller::curr();

        if (!$controller) {
            return i18n::get_locale();
        }

        $request = $controller->getRequest();

        if (!$request) {
            return i18n::get_locale();
        }

        return $request->getVar('locale') ?? i18n::get_locale();
    }

    public function getCMSFields()
    {
        $locale = $this->getLocaleFromRequest();

        $fields = parent::getCMSFields();

        $fields->removeByName('Updated');

        foreach ($fields->findOrMakeTab('Root.Main')->Fields() as $field) {
            $fields->makeFieldReadonly($field);
        }

        $fields->addFieldToTab('Root.AttributeValues', new GridField('AttributeValues', 'AttributeValues', $this->AttributeValues()->filter('Locale.Code', [$locale, null]), GridFieldConfig_RecordViewer::create()));

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

    public function populateAkeneoData(array $akeneoProduct, array $relatedObjectIds = []): void
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

    public function getLabel(): string
    {
        return $this->getLabelFromAttribute();
    }

    public function getLabelFromAttribute(): string
    {
        $attributeAsLabelCode = $this->Family()->AttributeAsLabel()->Code;

        return $this->AttributeValues()->find('Attribute.Code', $attributeAsLabelCode)?->getValue() ?? 'unknown';
    }

    public function getLocalisedAttributeValues(?string $locale = null): DataList
    {
        if (!$locale) {
            $locale = $this->getLocaleFromRequest();
        }

        return $this->getAttributeValuesForLocale($locale);
    }

    public function getLocalisedAttributeByCode(string $code, ?string $locale = null)
    {
        if (!$locale) {
            $locale = $this->getLocaleFromRequest();
        }

        return $this->getAttributeValueForLocale($code, $locale);
    }

    public function getAttributeValueForLocale(string $code, ?string $locale = null): mixed
    {
        /** @var ProductAttributeValue|null $attributeValue */
        $attributeValue = $this->AttributeValues()->filter([
            'Attribute.Code' => $code,
        ])->filterAny([
            'Locale.Code' => $locale,
            'LocaleID' => 0,
        ])->first();

        return $attributeValue?->getValue() ?? '';
    }

    public function getAttributeValuesForLocale(string $locale): DataList
    {
        return $this->AttributeValues()->filterAny([
            'Locale.Code' => $locale,
            'LocaleID' => 0,
        ]);
    }

    /**
     * @return ArrayList<ArrayData<string, string|Product>>
     */
    public function getRelatedProducts(): ArrayList
    {
        $relatedProducts = ArrayList::create();

        foreach ($this->Associations() as $association) {
            $associationType = $association->Type;

            $relatedProducts->add(
                ArrayData::create([
                    'Type' => $associationType,
                    'Product' => $association->RelatedProduct(),
                ])
            );
        }

        return $relatedProducts;
    }

    /**
     * @param string|null $type
     * @return ArrayList<Product>
     */
    public function getRelatedProductsByType(?string $type): ArrayList
    {
        $relatedProducts = ArrayList::create();

        foreach ($this->Associations() as $association) {
            if (!$type) {
                $relatedProducts->add($association->RelatedProduct());
                continue;
            }

            if ($type === $association->Type) {
                $relatedProducts->add($association->RelatedProduct());
            }
        }

        return $relatedProducts;
    }
}
