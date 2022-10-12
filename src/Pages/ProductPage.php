<?php

namespace WeDevelop\Akeneo\Pages;

use SilverStripe\Control\Director;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Security\Member;
use WeDevelop\Akeneo\Models\Product;
use WeDevelop\Akeneo\Models\ProductAssociation;
use WeDevelop\Akeneo\Models\ProductAttributeValue;
use WeDevelop\Akeneo\Models\ProductCategory;

class ProductPage extends \Page
{
    /** @config */
    private static string $singular_name = 'Product';

    /** @config */
    private static string $plural_name = 'Products';

    /** @config */
    private static array $has_one = [
        'Product' => Product::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'ID',
        'SKU' => 'Product.SKU',
        'Title',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Updated');
        $fields->removeByName('SEO');
        $fields->removeByName('OpenGraph');

        foreach ($fields->findOrMakeTab('Root.Main')->Fields() as $field) {
            $fields->makeFieldReadonly($field);
        }

        return $fields;
    }

    /**
     * @param Member $member
     * 
     * @return bool
     */
    public function canPublish($member = null)
    {
        if (Director::is_cli()) {
            return true;
        }

        return parent::canPublish($member);
    }

    /**
     * @param Member $member
     *
     * @return false
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * @param Member $member
     * @param array $context
     *
     * @return false
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }
}
