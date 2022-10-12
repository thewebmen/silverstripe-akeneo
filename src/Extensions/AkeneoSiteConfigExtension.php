<?php

namespace WeDevelop\Akeneo\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class AkeneoSiteConfigExtension extends DataExtension
{
    /** @config */
    private static array $db = [
        'AkeneoURL' => 'Varchar(255)',
        'AkeneoClientID' => 'Varchar(255)',
        'AkeneoSecret' => 'Varchar(100)',
        'AkeneoUsername' => 'Varchar(100)',
        'AkeneoPassword' => 'Varchar(100)',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->addFieldsToTab('Root.Akeneo', [
            TextField::create('AkeneoURL', 'URL'),
            TextField::create('AkeneoClientID', 'Client ID'),
            TextField::create('AkeneoSecret', 'Secret'),
            TextField::create('AkeneoUsername', 'Username'),
            TextField::create('AkeneoPassword', 'Password'),
        ]);
    }
}
