<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Security\Member;

class ProductAttributeOption extends AbstractAkeneoTranslateable implements AkeneoImportInterface
{
    /** @config */
    private static string $table_name = 'Akeneo_ProductAttributeOption';

    /** @config */
    private static $singular_name = 'Attribute Option';

    /** @config */
    private static $plural_name = 'Attribute Options';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(255)',
        'Sort' => 'Int',
        'Updated' => 'Boolean',
    ];

    /** @config */
    private static array $has_one = [
        'Attribute' => ProductAttribute::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'Code',
        'Name',
    ];

    /** @config */
    private static string $default_sort = '"Sort"';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Sort');
        $fields->removeByName('Updated');
        $fields->removeByName('Translations');

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

    public function populateAkeneoData(array $akeneoItem, array $relatedObjectIds): void
    {
        $this->Code = $akeneoItem['code'];
        $this->Sort = $akeneoItem['sort_order'];
        $this->Updated = true;

        foreach ($relatedObjectIds as $field => $value) {
            $this->{$field} = $value;
        }

        $this->updateLabels($akeneoItem);
    }

    public function getImportOutput(): string
    {
        return $this->singular_name() . ': ' . $this->ID . ' - ' . $this->Name . '(' . $this->Attribute->Code . '}';
    }

    public static function getParentRelation(): string
    {
        return 'Attribute';
    }

    public static function getIdentifierField(): string
    {
        return 'Code';
    }
}
