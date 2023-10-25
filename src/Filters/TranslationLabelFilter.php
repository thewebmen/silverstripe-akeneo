<?php

namespace WeDevelop\Akeneo\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\SearchFilter;
use WeDevelop\Akeneo\Models\ProductAttribute;

class TranslationLabelFilter extends SearchFilter
{
    public function applyOne(DataQuery $query)
    {
        $value = $this->getValue();

        $query = call_user_func([ProductAttribute::class, 'filterByLabel'], $query, $value);

        return $query;
    }

    protected function excludeOne(DataQuery $query)
    {
        // TODO: Implement excludeOne() method.
    }
}
