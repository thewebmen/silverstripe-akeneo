<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

/**
 * @property ?string $Label
 */
class LabelTranslation extends DataObject
{
    /** @config */
    private static string $table_name = 'Akeneo_Label_Translations';

    /**
     * @config
     * @var array<string, string>
     */
    private static array $db = [
        'Label' => 'Varchar',
    ];

    /**
     * @config
     * @var array<string, string>
     */
    private static array $summary_fields = [
        'ID' => 'ID',
        'Label' => 'Label',
        'Locale.Code' => 'Locale',
    ];

    /** @config */
    private static string $default_sort = 'Label';

    /**
     * @config
     * @var array<string, class-string>
     */
    private static array $has_one = [
        'Locale' => Locale::class,
        'Family' => Family::class,
        'FamilyVariant' => FamilyVariant::class,
        'ProductAttribute' => ProductAttribute::class,
        'ProductAttributeGroup' => ProductAttributeGroup::class,
        'ProductAttributeOption' => ProductAttributeOption::class,
        'ProductAssociation' => ProductAssociation::class,
        'ProductCategory' => ProductCategory::class,
        'ProductModel' => ProductModel::class,
        'ProductMediaFile' => ProductMediaFile::class,
        'ProductImage' => ProductImage::class,
    ];

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        foreach ($fields->findOrMakeTab('Root.Main')->Fields() as $field) {
            $fields->makeFieldReadonly($field);
        }

        return $fields;
    }
}
