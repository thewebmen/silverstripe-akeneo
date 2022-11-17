<?php

namespace WeDevelop\Akeneo\Models;

interface AkeneoImportInterface
{
    public static function getIdentifierField(): string;

    public function getImportOutput(): string;

    public function populateAkeneoData(array $akeneoItem, array $relatedObjectIds): void;
}
