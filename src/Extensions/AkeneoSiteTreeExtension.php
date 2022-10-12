<?php

namespace WeDevelop\Akeneo\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;

class AkeneoSiteTreeExtension extends DataExtension
{
    public function augmentStageChildren(DataList &$staged): void
    {
        if ($this->owner->ClassName === SiteTree::class && $this->owner->ID === 0) {
            if ($excludedPages = $this->owner->config()->get('excluded_root_pages')) {
                $staged = $staged->Filter('ClassName:not', $excludedPages);
            }
        }
    }
}
