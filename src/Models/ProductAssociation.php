<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Security\Member;

class ProductAssociation extends AbstractAkeneoTranslateable
{
    /** @config */
    private static string $table_name = 'Akeneo_ProductAssociation';

    /** @config */
    private static string $singular_name = 'ProductAssociation';

    /** @config */
    private static string $plural_name = 'ProductAssociations';

    /** @config */
    private static array $db = [
        'Type' => 'Varchar(255)'
    ];

    /** @config */
    private static array $has_one = [
        'Product' => Product::class,
        'ProductModel' => ProductModel::class,
        'RelatedProduct' => Product::class,
        'RelatedProductModel' => ProductModel::class,
    ];

    /** @config */
    private static array $indexes = [
        'ProductIndex' => ['Type', 'ProductID'],
        'ProductModelIndex' => ['Type', 'ProductModelID'],
    ];

    /** @config */
    private static array $summary_fields = [
        'Type' => 'Type',
        'RelatedType' => 'Related Type',
        'RelatedObjectIdentifier' => 'Related Object',
    ];


    public function getRelatedType(): string
    {
        if ($this->getField('RelatedProduct')->ID) {
            return 'Product';
        }

        if ($this->getField('RelatedProductModel')->ID) {
            return 'ProductModel';
        }

        return '';
    }

    public function getRelatedObjectIdentifier(): string
    {
        if ($this->getField('RelatedProduct')->ID) {
            return $this->getField('RelatedProduct')->SKU;
        }

        if ($this->getField('RelatedProductModel')->ID) {
            return $this->getField('RelatedProductModel')->Code;
        }

        return '';
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
     *
     * @return false
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }
}
