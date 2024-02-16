<?php

declare(strict_types=1);

namespace WeDevelop\Akeneo\Models;

use SilverStripe\ORM\DataObjectInterface;

interface AkeneoImportInterface extends DataObjectInterface
{
    public static function getIdentifierField(): string;

    public function getImportOutput(): string;

    public function populateAkeneoData(array $akeneoItem, array $relatedObjectIds): void;
}
