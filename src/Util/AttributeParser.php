<?php

declare(strict_types=1);

namespace WeDevelop\Akeneo\Util;

use WeDevelop\Akeneo\Models\ProductAttributeOption;
use WeDevelop\Akeneo\Models\ProductAttributeValue;

abstract class AttributeParser
{
    public static function MetricTypeParser(ProductAttributeValue $value): ?string
    {
        if ($value->Attribute()->Type !== ProductAttributeValue::PIM_CATALOG_METRIC_TYPE) {
            throw new \RuntimeException('Not a metric attribute value');
        }

        $jsonValue = $value->getField('Value');
        if (empty($jsonValue)) {
            return null;
        }

        /** @var array{amount: string, unit: string}|mixed $decodedValue */
        $decodedValue = json_decode($jsonValue, true);
        if (!is_array($decodedValue)) {
            return null;
        }

        return vsprintf('%s %s', [
            round(floatval($decodedValue['amount']), 2),
            _t(__CLASS__ . '.' . strtoupper($decodedValue['unit']), ucfirst(strtolower($decodedValue['unit']))),
        ]);
    }

    public static function MultiSelectParser(ProductAttributeValue $value): string
    {
        if ($value->Attribute()->Type !== ProductAttributeValue::PIM_CATALOG_MULTISELECT_TYPE) {
            throw new \RuntimeException('Not a multi select attribute value');
        }

        $jsonValues = $value->getField('Value');
        if (empty($jsonValues)) {
            return null;
        }

        $attributeNames = array_map(static function (ProductAttributeOption $option) {
            return $option->getName();
        }, $value->Attribute()->Options()->filter('Code', $jsonValues)->toArray());

        return implode(', ', $attributeNames);
    }

    public static function PriceCollectionParser(ProductAttributeValue $value): string
    {
        if ($value->Attribute()->Type !== ProductAttributeValue::PIM_CATALOG_PRICE_COLLECTION) {
            throw new \RuntimeException('Not a price collection attribute value');
        }

        /** @var array<array{amount: string, currency: string}>|mixed $decodedValue */
        $jsonValue = $value->getField('Value');
        if (!is_array($jsonValue) || empty($jsonValue[0])) {
            return null;
        }

        return $jsonValue[0]['currency'] . ' ' . $jsonValue[0]['amount'];
    }
}
