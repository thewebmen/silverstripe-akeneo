<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Member;
use WeDevelop\Akeneo\Filters\TranslationLabelFilter;

/**
 * @method HasManyList<ProductAttributeOption> Options()
 */
class ProductAttribute extends AbstractAkeneoTranslateable implements AkeneoImportInterface
{
    /** @config */
    private static string $table_name = 'Akeneo_ProductAttribute';

    /** @config */
    private static string $singular_name = 'Attribute';

    /** @config */
    private static string $plural_name = 'Attributes';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(255)',
        'Type' => 'Varchar(255)',
        'Sort' => 'Int',
        'Updated' => 'Boolean',
    ];

    /** @config */
    private static array $has_many = [
        'Options' => ProductAttributeOption::class,
        'Values' => ProductAttributeValue::class,
    ];

    /** @config */
    private static array $has_one = [
        'Group' => ProductAttributeGroup::class,
    ];

    /** @config */
    private static array $belongs_many_many = [
        'Families' => Family::class,
    ];

    /** @config */
    private static array $cascade_deletes = [
        'Options',
        'Values',
    ];

    /** @config */
    private static array $summary_fields = [
        'getLabelByLocale' => 'Label',
        'Code' => 'Code',
        'Type' => 'Type',
        'Options.Count' => 'Options',
    ];

    private static array $searchable_fields = [
        'LabelByLocale' => [
            'title' => 'Label',
            'filter' => TranslationLabelFilter::class,
            'relation' => 'LabelTranslations',
        ],
    ];

    /** @config */
    private static string $default_sort = 'Sort';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Updated');
        $fields->removeByName('Sort');
        $fields->removeByName('Values');
        $fields->removeByName('Options');
        $fields->removeByName('Translations');

        foreach ($fields->findOrMakeTab('Root.Main')->Fields() as $field) {
            $fields->makeFieldReadonly($field);
        }

        if ($this->Options()->count() > 0) {
            $fields->addFieldToTab(
                'Root.Options',
                GridField::create('Options', 'Options', $this->Options(), GridFieldConfig_RecordEditor::create())
            );
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
        // setting this to true to be able to use GridFieldSortableRows component
        return true;
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

    public function populateAkeneoData(array $akeneoItem, array $relatedObjectIds = []): void
    {
        $this->Code = $akeneoItem['code'];
        $this->Type = $akeneoItem['type'];
        $this->Sort = $akeneoItem['sort_order'];

        $this->Updated = true;

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

    public function getLabelByLocale(): string
    {
        if (class_exists('TractorCow\Fluent\Model\Locale')) {
            $localeObj = call_user_func(['TractorCow\Fluent\Model\Locale', 'getCurrentLocale']);
            $locale = $localeObj->Locale;
        } else {
            $locale = i18n::get_locale();
        }

        /** @var LabelTranslation|null $labelTranslation */
        $labelTranslation = $this->LabelTranslations()->filter('Locale.Code', $locale)->first();

        return $labelTranslation !== null ? $labelTranslation->Label : '';
    }

    public static function filterByLabel(DataQuery $query, string $value, string $locale = 'nl_NL')
    {
        $labelTranslationIDs = LabelTranslation::get()->filter(['Label:PartialMatch' => $value])->column('ID');
        $query->leftJoin('Akeneo_Label_Translations', '"Akeneo_Label_Translations"."ProductAttributeID" = "Akeneo_ProductAttribute"."ID"');

        /** @var Locale|null $locale */
        $locale = Locale::get()->filter(['code' => $locale])->first();

        if (empty($labelTranslationIDs) || ($locale === null)) {
            $query->where("0 = 1");
        } else {
            $idsString = implode(',', $labelTranslationIDs);
            $query->where(sprintf('"Akeneo_Label_Translations"."ID" IN (%s) and "Akeneo_Label_Translations"."LocaleID" = %d', $idsString, $locale->ID));
        }

        $query->selectField('"Akeneo_Label_Translations"."Label"', 'SearchLabel');

        $query->sort();
        return $query;
    }
}
