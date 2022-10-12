<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

class ProductCategory extends DataObject implements AkeneoImportInterface
{
    /** @config */
    private static string $table_name = 'Akeneo_ProductCategory';

    /** @config */
    private static string $singular_name = 'Category';

    /** @config */
    private static string $plural_name = 'Categories';

    /** @config */
    private static array $db = [
        'Sort' => 'Int',
        'Code' => 'Varchar(255)',
        'Name' => 'Varchar(255)',
        'Updated' => 'Boolean',
    ];

    private static array $has_one = [
        'Parent' => self::class
    ];

    /* @config */
    private static array $summary_fields = [
        'Code',
        'Name',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Updated');
        $fields->removeByName('Translations');

        return $fields;
    }

    public function Children(): DataList
    {
        return self::get()->filter('ParentID', $this->ID);
    }

    /**
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
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

    public function populateAkeneoData(array $akeneoItem, string $locale, array $relatedObjectIds): void
    {
        $this->Code = $akeneoItem['code'];
        $this->Updated = true;
        $this->Name = $akeneoItem['labels'][$locale] ?? $akeneoItem['code'];
        foreach ($relatedObjectIds as $field => $value) {
            $this->{$field} = $value;
        }
    }

    public function getImportOutput(): string
    {
        return $this->singular_name().': '.$this->ID.' - '.$this->Name.'('.$this->Parent->Name.')';
    }

    public static function getIdentifierField(): string
    {
        return 'Code';
    }
}
