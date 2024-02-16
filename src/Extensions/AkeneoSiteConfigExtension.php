<?php

namespace WeDevelop\Akeneo\Extensions;

use GuzzleHttp\Exception\ClientException;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataExtension;
use WeDevelop\Akeneo\Service\AkeneoApi;

class AkeneoSiteConfigExtension extends DataExtension
{
    private AkeneoApi $akeneoApi;

    /** @config */
    private static array $db = [
        'AkeneoURL' => 'Varchar(255)',
        'AkeneoClientID' => 'Varchar(255)',
        'AkeneoSecret' => 'Varchar(100)',
        'AkeneoUsername' => 'Varchar(100)',
        'AkeneoPassword' => 'Varchar(100)',
        'AkeneoChannel' => 'Varchar(100)',
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
        if ($this->canConnect()) {
            $fields->addFieldToTab(
                'Root.Akeneo',
                DropdownField::create('AkeneoChannel', 'Channel', $this->getAkeneoChannels())
            );
        }
    }

    /**
     * Do some cleanup before we write to the database
     *
     * @return void
     */
    public function onBeforeWrite()
    {
        $this->getOwner()->AkeneoURL = rtrim($this->getOwner()->AkeneoURL ?? '', '/ ');
        parent::onBeforeWrite();
    }

    private function credentialsExist(): bool
    {
        return $this->getOwner()->AkeneoURL &&
            $this->getOwner()->AkeneoClientID &&
            $this->getOwner()->AkeneoSecret &&
            $this->getOwner()->AkeneoUsername &&
            $this->getOwner()->AkeneoPassword;
    }

    private function canConnect(): bool
    {
        if (!$this->credentialsExist()) {
            return false;
        }

        $this->akeneoApi = new AkeneoApi();

        try {
            $this->akeneoApi->authorize();
        } catch (ClientException) {
            return false;
        }

        return true;
    }

    private function getAkeneoChannels(): array
    {
        $channels = $this->akeneoApi->getChannels();
        $locale = i18n::get_locale();
        $options = [];

        foreach ($channels['_embedded']['items'] as $channel) {
            $options[$channel['code']] = array_key_exists($locale, $channel['labels']) ? $channel['labels'][$locale] : $channel['code'];
        }

        return $options;
    }
}
