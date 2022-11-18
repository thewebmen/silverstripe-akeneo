<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;

class ProductImage extends Image
{
    /** @config */
    private static string $table_name = 'Akeneo_ProductImage';

    /** @config */
    private static string $singular_name = 'Product Image';

    /** @config */
    private static string $plural_name = 'Product Images';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(255)',
    ];

    /** @config */
    private static array $has_one = [
        'Locale' => Locale::class
    ];

    public static function createFromAkeneoData(array $data, string $content): self
    {
        $productImage = new self();
        $productImage->setFromString($content, sprintf('%s_%s', base64_encode($data['code']), $data['original_filename']));

        $productImage->Code = $data['code'];
        $productImage->Title = $data['original_filename'];
        $productImage->ParentID = self::getParentID();
        $productImage->File->MimeType = $data['mime_type'];
        $productImage->Size = $data['size'];

        $productImage->write();

        if ($productImage->canPublish()) {
            $productImage->publishSingle();
        }

        return $productImage;
    }

    public static function getParentID(): int
    {
        return Folder::find_or_make('Product/image')->ID;
    }

    public function canPublish($member = null)
    {
        return true;
    }
}
