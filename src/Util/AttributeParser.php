<?php

declare(strict_types=1);

namespace WeDevelop\Akeneo\Util;

use WeDevelop\Akeneo\Enums\ProductAttributeType;
use WeDevelop\Akeneo\Models\ProductAttributeOption;
use WeDevelop\Akeneo\Models\ProductAttributeValue;

abstract class AttributeParser
{
    public static function MetricTypeParser(ProductAttributeValue $value): ?string
    {
        if (ProductAttributeType::tryFrom($value->Attribute()->Type) !== ProductAttributeType::METRIC) {
            throw new \RuntimeException('Not a metric attribute value');
        }

        $jsonValue = $value->getField('Value');
        if (empty($jsonValue)) {
            return null;
        }

        /** @var array{amount: string, unit: string}|mixed $decodedValue */
        $decodedValue = json_decode((string)$jsonValue, true);
        if (!is_array($decodedValue)) {
            return null;
        }

        return vsprintf('%s %s', [
            round((float)$decodedValue['amount'], 2),
            _t(self::class . '.' . strtoupper((string)$decodedValue['unit']), ucfirst(strtolower((string)$decodedValue['unit']))),
        ]);
    }

    public static function MultiSelectParser(ProductAttributeValue $value): string
    {
        if (ProductAttributeType::tryFrom($value->Attribute()->Type) !== ProductAttributeType::MULTISELECT) {
            throw new \RuntimeException('Not a multi select attribute value');
        }

        $jsonValues = $value->getField('Value');
        if (empty($jsonValues)) {
            return '';
        }

        try {
            $parsedJSON = json_decode((string)$jsonValues, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception) {
            return '';
        }

        $attributeNames = array_map(static fn (ProductAttributeOption $option) => $option->getName(), $value->Attribute()->Options()->filter('Code', $parsedJSON)->toArray());

        return implode(', ', $attributeNames);
    }

    public static function PriceCollectionParser(ProductAttributeValue $value): string
    {
        if (ProductAttributeType::tryFrom($value->Attribute()->Type) !== ProductAttributeType::PRICE_COLLECTION) {
            throw new \RuntimeException('Not a price collection attribute value');
        }

        /** @var array<array{amount: string, currency: string}>|mixed $jsonValue */
        $jsonValue = $value->getField('Value');
        if (!is_array($jsonValue) || empty($jsonValue[0])) {
            return '';
        }

        return $jsonValue[0]['currency'] . ' ' . $jsonValue[0]['amount'];
    }
}
