<?php

namespace WeDevelop\Akeneo\Models;

interface AkeneoTranslateableInterface
{
    public function getLabel(): string;

    public function getLabelForLocale(string $localeCode): string;
}
