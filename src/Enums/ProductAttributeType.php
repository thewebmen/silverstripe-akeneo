<?php

declare(strict_types=1);

namespace WeDevelop\Akeneo\Enums;

enum ProductAttributeType: string
{
    case BOOLEAN = 'pim_catalog_boolean';
    case DATE = 'pim_catalog_date';
    case FILE = 'pim_catalog_file';
    case IMAGE = 'pim_catalog_image';
    case METRIC = 'pim_catalog_metric';
    case MULTISELECT = 'pim_catalog_multiselect';
    case PRICE_COLLECTION = 'pim_catalog_price_collection';
    case SIMPLESELECT = 'pim_catalog_simpleselect';
    case TEXT = 'pim_catalog_text';
    case TEXTAREA = 'pim_catalog_textarea';
}
