<?php

namespace WeDevelop\Akeneo\Models;

use SilverStripe\Control\Controller;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;

/**
 * @method HasManyList<LabelTranslation> LabelTranslations()
 */
class AbstractAkeneoTranslateable extends DataObject implements AkeneoTranslateableInterface
{
    /**
     * @config
     * @var array<string, class-string>
     */
    private static array $has_many = [
        'LabelTranslations' => LabelTranslation::class,
    ];

    public function getTitle(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        $locale = $this->getLocaleFromRequest();
        return $this->getLabelForLocale($locale);
    }

    public function getLabelForLocale(string $localeCode): string
    {
        /** @var LabelTranslation $translation */
        $translation = $this->LabelTranslations()->find('Locale.Code', $localeCode);

        return $translation->Label ?? $this->Code ?? '';
    }

    public function getName(): string
    {
        return $this->getLabel();
    }

    public function updateLabels(array $akeneoItem): void
    {
        if (!array_key_exists('labels', $akeneoItem)) {
            return;
        }

        foreach (array_keys($akeneoItem['labels']) as $locale) {
            /** @var LabelTranslation|null $label */
            $label = $this->LabelTranslations()->find('Locale.Code', $locale);
            $label ??= new LabelTranslation();
            /** @var Locale|null $localeModel */
            $localeModel = Locale::get()->find('Code', $locale);

            if (!$localeModel) {
                $localeModel = new Locale();
                $localeModel->Code = $locale;
                $localeModel->write();
            }

            $label->LocaleID = $localeModel->ID;
            $label->Label = $akeneoItem['labels'][$locale];
            $this->LabelTranslations()->add($label);
        }
    }

    public function getLocaleFromRequest(): string
    {
        if (!Controller::has_curr()) {
            return i18n::get_locale();
        }

        $request = Controller::curr()->getRequest();

        if ($request instanceof NullHTTPRequest) {
            return i18n::get_locale();
        }

        return $request->getVar('locale') ?? i18n::get_locale();
    }
}
