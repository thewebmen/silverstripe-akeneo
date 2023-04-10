<?php

namespace WeDevelop\Akeneo\Models\Display;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\UnsavedRelationList;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;
use WeDevelop\Akeneo\Admins\AkeneoAdmin;
use WeDevelop\Akeneo\Models\ProductAttribute;

/**
 * @property Boolean $IsRootGroup
 * @method ManyManyList|UnsavedRelationList<DisplayGroup> DisplayGroups
 * @method ManyManyList|UnsavedRelationList<DisplayGroup> ParentDisplayGroups
 */
class DisplayGroup extends DataObject
{
    /** @config */
    private static string $table_name = 'Akeneo_DisplayGroup';

    /** @config */
    private static string $singular_name = 'Display Group';

    /** @config */
    private static string $plural_name = 'Display Groups';

    /** @var array<string, string> @config */
    private static array $db = [
        'Title' => 'Varchar',
        'IsRootGroup' => 'Boolean(0)',
    ];

    /** @var array<string, string> @config */
    private static array $many_many = [
        'ProductAttributes' => ProductAttribute::class,
        'DisplayGroups' => DisplayGroup::class,
    ];

    /** @var array<string, array<string, string>> @config */
    private static $many_many_extraFields = [
        'ProductAttributes' => [
            'SortOrder' => 'Int',
        ],
        'DisplayGroups' => [
            'SortOrder' => 'Int',
        ],
    ];

    /** @var array<string, string> @config */
    private static array $belongs_many_many = [
        'ParentDisplayGroups' => DisplayGroup::class . '.DisplayGroups',
    ];

    private const RECURSION_MAX_DEPTH = 20;
    private static int $RECURSION_COUNTER = 0;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'IsRootGroup',
            'ProductAttributes',
            'DisplayGroups',
            'ParentDisplayGroups',
        ]);

        $fields->addFieldsToTab('Root.Main', [
            HeaderField::create('HierarchyHeader', 'Below is the hierarchical structure of this group, please note that this does not include any parent group'),
            $this->getHierarchyLiteralField(),
            $this->getDisplayGroupsGridField(),
            $this->getAttributesGridField(),
        ]);

        return $fields;
    }

    public function getProductAttributes(): ManyManyList|UnsavedRelationList
    {
        return $this
            ->getManyManyComponents('ProductAttributes')
            ->sort('SortOrder');
    }

    public function getDisplayGroups(): ManyManyList|UnsavedRelationList
    {
        return $this
            ->getManyManyComponents('DisplayGroups')
            ->sort('SortOrder');
    }

    private function getAttributesGridField(): GridField
    {
        $config = GridFieldConfig_RelationEditor::create(10);
        $gridField = GridField::create('ProductAttributes', 'Product Attributes', $this->getProductAttributes(), $config);

        $config->addComponents([
            GridFieldSortableRows::create('SortOrder'),
        ]);

        return $gridField;
    }

    private function getDisplayGroupsGridField(): GridField
    {
        $config = GridFieldConfig_RelationEditor::create(10);
        $gridField = GridField::create('DisplayGroups', 'Display Groups', $this->getDisplayGroups(), $config);

        $config->addComponents([
            GridFieldSortableRows::create('SortOrder'),
        ]);

        /** @var GridFieldAddExistingAutocompleter $addExistingAutocompleter */
        $addExistingAutocompleter = $config->getComponentByType(GridFieldAddExistingAutocompleter::class);

        $addExistingAutocompleter->setSearchList(
            DisplayGroup::get()->filter([
                'ID:not' => array_merge([$this->ID], $this->ParentDisplayGroups()->column('ID'))
            ]),
        );

        return $gridField;
    }

    public function getHierarchyHTML(): string
    {
        $attributes = $this->getProductAttributes();
        $DisplayGroups = $this->getDisplayGroups();

        $html = '<ul>';
        $html .= '<li><a href="' . $this->getCMSEditLink() . '" target="_blank">' . $this->getTitle() . '</a></li>';
        $html .= '<ol>';

        /** @var ProductAttribute $attribute */
        foreach ($attributes as $attribute) {
            $html .= '<li>' . $attribute->getLabel()() . '</li>';
        }

        $html .= '</ol>';

        /** @var DisplayGroup $DisplayGroup */
        foreach ($DisplayGroups as $DisplayGroup) {
            if (self::$RECURSION_COUNTER > self::RECURSION_MAX_DEPTH) {
                $html .= '</ul>';

                return $html;
            }

            self::$RECURSION_COUNTER++;

            $hierarchyHTML = '<li>' . $DisplayGroup->getHierarchyHTML() . '</li>';

            if ($hierarchyHTML) {
                $html .= $hierarchyHTML;
            }
        }

        $html .= '</ul>';

        return $html;
    }

    private function getHierarchyLiteralField(): LiteralField
    {
        return LiteralField::create('Hierarchy', $this->getHierarchyHTML());
    }

    public static function getRootGroups()
    {
        return self::get()->filter([
            'IsRootGroup' => 1
        ]);
    }

    public static function getNonRootGroups()
    {
        return self::get()->filter([
            'IsRootGroup' => 0
        ]);
    }

    public function onBeforeWrite()
    {
        $this->IsRootGroup = $this->ParentDisplayGroups()->count() === 0;

        parent::onBeforeWrite();
    }

    public function getCMSEditLink(): string
    {
        $admin = AkeneoAdmin::singleton();

        if ($this->IsRootGroup) {
            $subTab = 'RootDisplayGroup';
        }

        return $admin->getCMSEditLink($this, $subTab ?? '');
    }
}
