<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;

class ProductMediaFile extends DataObject
{
    /** @config */
    private static string $table_name = 'Akeneo_ProductMediaFile';

    /** @config */
    private static string $singular_name = 'Product Media File';

    /** @config */
    private static string $plural_name = 'Product Media Files';

    /** @config */
    private static array $db = [
        'Code' => 'Varchar(255)',
    ];

    /** @config */
    private static array $has_one = [
        'Document' => File::class,
        'Image' => Image::class,
        'Locale' => Locale::class,
    ];

    public static function createFromAkeneoData(array $data, string $content): self
    {
        $productMediaFile = new self();

        $file = self::createFile($data, $content);

        if ($file instanceof Image) {
            $productMediaFile->ImageID = $file->ID;
        } else {
            $productMediaFile->DocumentID = $file->ID;
        }

        $productMediaFile->Code = $data['code'];

        $productMediaFile->write();

        return $productMediaFile;
    }

    public function getAttributeValue()
    {
        if ($this->DocumentID) {
            return $this->Document;
        }

        if ($this->ImageID) {
            return $this->Image;
        }

        return null;
    }

    public static function getFolderID(string $type): int
    {
        return Folder::find_or_make('Product/' . $type)->ID;
    }

    private static function createFile(array $data, string $content): File
    {
        $mediaFileClass = File::get_class_for_file_extension($data['extension']);

        /** @var File $file */
        $file = new $mediaFileClass();
        $file->setFromString($content, sprintf('%s_%s', substr(hash('sha256', $data['code']), 0, 8), $data['original_filename']));

        $type = $file->appCategory();

        $file->Title = $data['original_filename'];

        $file->ParentID = self::getFolderID($type);
        $file->File->MimeType = $data['mime_type'];
        $file->Size = $data['size'];

        $file->write();
        $file->publishSingle();

        return $file;
    }
}
