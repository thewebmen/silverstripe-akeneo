<?php

declare(strict_types=1);

namespace WeDevelop\Akeneo\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\SearchFilter;
use WeDevelop\Akeneo\Models\ProductAttribute;

class TranslationLabelFilter extends SearchFilter
{
    protected function applyOne(DataQuery $query): DataQuery
    {
        $value = $this->getValue();

        $query = call_user_func(ProductAttribute::filterByLabel(...), $query, $value);

        return $query;
    }

    protected function excludeOne(DataQuery $query)
    {
        throw new \RuntimeException('Not implemented yet.');
    }
}
