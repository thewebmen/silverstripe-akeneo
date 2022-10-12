<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use WeDevelop\Akeneo\Models\ProductAttribute;

class FamilyVariant extends DataObject implements AkeneoImportInterface
{
    /** @config */
    private static string $table_name = 'Akeneo_FamilyVariant';

    /** @config */
    private static string $singular_name = 'Variant';

    /** @config */
    private static string $plural_name = 'Variants';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(255)',
        'Name' => 'Varchar(255)',
        'Updated' => 'Boolean',
    ];

    /** @config */
    private static array $has_one = [
        'Family' => Family::class,
    ];

    public function populateAkeneoData(array $akeneoItem, string $locale, array $relatedObjectIds = []): void
    {
        $this->Code = $akeneoItem['code'];
        $this->Name = $akeneoItem['labels'][$locale] ?? $akeneoItem['code'];
        $this->Updated = true;

        foreach ($relatedObjectIds as $field => $value) {
            $this->{$field} = $value;
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
     * @param array $context
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function getImportOutput(): string
    {
        return $this->singular_name().': '.$this->ID.' - '.$this->Name;
    }

    public static function getParentRelation(): string
    {
        return 'Family';
    }

    public static function getIdentifierField(): string
    {
        return 'Code';
    }
}
