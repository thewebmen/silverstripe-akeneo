<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\ORM\DataObjectInterface;

interface AkeneoImportInterface extends DataObjectInterface
{
    public static function getIdentifierField(): string;

    public function getImportOutput(): string;

    public function populateAkeneoData(array $akeneoItem, string $locale, array $relatedObjectIds): void;
}
