<?php

namespace WeDevelop\Akeneo\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;

class AkeneoSiteTreeExtension extends DataExtension
{
    public function augmentStageChildren(DataList &$staged): void
    {
        if ($this->getOwner()->ClassName === SiteTree::class && $this->getOwner()->ID === 0 && ($excludedPages = $this->getOwner()->config()->get('excluded_root_pages'))) {
            $staged = $staged->Filter('ClassName:not', $excludedPages);
        }
    }
}
