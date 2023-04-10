<?php

namespace WeDevelop\Config;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;

class AkeneoConfig
{
    use Configurable;

    /** @var bool @config */
    private static $enable_display_groups = true;

    public static function getEnableDisplayGroups()
    {
        return Config::inst()->get(static::class, 'enable_display_groups');
    }
}
