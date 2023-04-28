<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\ORM\DataObject;

class Locale extends DataObject
{
    /** @config */
    private static string $table_name = 'Akeneo_Locale';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(8)',
    ];

    /** @config */
    private static array $has_many = [
        ProductAttributeValue::class,
        ProductImage::class,
        ProductMediaFile::class,
        LabelTranslation::class,
    ];
}
