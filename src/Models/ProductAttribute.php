<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Member;

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
        'Code' => 'Code',
        'Type' => 'Type',
        'Options.Count' => 'Options',
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
            $fields->addFieldToTab('Root.Options', new GridField('Options', 'Options', $this->Options(), GridFieldConfig_RecordEditor::create()));
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
}
