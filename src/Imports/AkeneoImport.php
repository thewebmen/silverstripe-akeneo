<?php

namespace WeDevelop\Akeneo\Imports;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ValidationException;
use WeDevelop\Akeneo\Models\AkeneoImportInterface;
use WeDevelop\Akeneo\Models\Family;
use WeDevelop\Akeneo\Models\FamilyVariant;
use WeDevelop\Akeneo\Models\Locale;
use WeDevelop\Akeneo\Models\Product;
use WeDevelop\Akeneo\Models\ProductAssociation;
use WeDevelop\Akeneo\Models\ProductAttribute;
use WeDevelop\Akeneo\Models\ProductAttributeGroup;
use WeDevelop\Akeneo\Models\ProductAttributeOption;
use WeDevelop\Akeneo\Models\ProductAttributeValue;
use WeDevelop\Akeneo\Models\ProductCategory;
use WeDevelop\Akeneo\Models\ProductMediaFile;
use WeDevelop\Akeneo\Models\ProductModel;
use WeDevelop\Akeneo\Service\AkeneoApi;

class AkeneoImport
{
    use Injectable;
    use Configurable;

    /** @var array<string,ProductCategory> */
    private array $categories = [];

    /** @var array<string,ProductAttribute> */
    private array $attributes = [];

    /** @var array<string,ProductAttributeGroup> */
    private array $attributeGroups = [];

    /** @var array<string,ProductAttributeOption> */
    private array $attributesOptions = [];

    /** @var array<string,Family> */
    private array $families = [];

    /** @var array<string,FamilyVariant> */
    private array $variants = [];

    /** @var array<string,ProductModel> */
    private array $productModels = [];

    /** @var array<string,Product> */
    private array $products = [];

    /** @var array<string,ProductMediaFile> */
    private array $productMediaFiles = [];

    private array $productModelsAssociations = [];
    private array $productsAssociations = [];

    private bool $verbose = true;

    private AkeneoApi $akeneoApi;

    private array $associationMapping = [
        'products' => [
            'class' => Product::class,
            'identifierField' => 'SKU',
            'relatedField' => 'RelatedProductID',
        ],
        'product_models' => [
            'class' => ProductModel::class,
            'identifierField' => 'Code',
            'relatedField' => 'RelatedProductModelID',
        ],
    ];

    private array $imports = [
        'categories' => ProductCategory::class,
        'attributeGroups' => ProductAttributeGroup::class,
        'attributes' => ProductAttribute::class,
        'attributeOptions' => ProductAttributeOption::class,
        'families' => Family::class,
        'variants' => FamilyVariant::class,
        'productModels' => ProductModel::class,
        'products' => Product::class,
    ];

    private array $importParents = [
        'attributeOptions' => [
            'class' => ProductAttribute::class,
            'filter' => [
                'type' => ['pim_catalog_simpleselect', 'pim_catalog_multiselect'],
            ],
        ],
        'variants' => [
            'class' => Family::class,
            'filter' => [
            ],
        ],
    ];

    private array $requiredParentImport = [
        'attributes' => 'attributeOptions',
        'families' => 'variants',
    ];

    private bool $hasProductImport = false;

    public function __construct()
    {
        $this->akeneoApi = new AkeneoApi();
        $this->akeneoApi->authorize();
    }

    /**
     * @param array<string> $imports
     * @return void
     */
    public function run(array $imports): void
    {
        foreach (array_keys($this->imports) as $type) {
            if (!empty($imports) && !in_array($type, $imports, true) && !$this->isRequiredParentImport($type, $imports)) {
                continue;
            }

            if (array_key_exists($type, $this->importParents)) {
                $parentClass = $this->importParents[$type]['class'];
                $parentRecords = $parentClass::get()->filter($this->importParents[$type]['filter']);
                /** @var AkeneoImportInterface $parentRecord */
                foreach ($parentRecords as $parentRecord) {
                    $this->import($type, $parentClass, $parentRecord->{$parentClass::getIdentifierField()});
                }
            } else {
                $this->import($type);
            }

            if (in_array($type, ['products', 'productModels'], true)) {
                $this->hasProductImport = true;
            }
        }

        if ($this->hasProductImport) {
            $this->setAssociations();
            $this->importMediaFiles();
        }
    }

    /**
     * @param array<string> $import
     */
    protected function isRequiredParentImport(string $type, array $import): bool
    {
        return array_key_exists($type, $this->requiredParentImport) &&
            in_array($this->requiredParentImport[$type], $import, true);
    }

    protected function import(string $type, ?string $parentImport = null, ?string $parentImportKey = null): void
    {
        $this->output("Import " . $type . ($parentImportKey ? " of {$parentImportKey}" : ''));
        $class = $this->imports[$type];

        if ($parentImport && $parentImportKey) {
            $this->prepareImportWithParent($type, $class, $parentImportKey);
        } else {
            $this->prepareImport($type, $class);
        }

        $page = 0;
        $limit = 50;

        do {
            $page++;
            $apiMethod = 'get' . ucfirst($type);

            $akeneoData = $this->akeneoApi->$apiMethod($page, $limit, $parentImportKey);
            $itemsCount = $akeneoData['items_count'];

            foreach ($akeneoData['_embedded']['items'] as $akeneoItem) {
                if ($this->shouldSkip($type, $akeneoItem)) {
                    $this->output("Skipping {$akeneoItem['code']}");
                    continue;
                }

                if ($type === 'attributeOptions') {
                    /** @var AkeneoImportInterface $record */
                    $record = $class::get()->filter([
                        'Code' => $akeneoItem['code'],
                        'Attribute.Code' => $parentImportKey,
                    ])->first() ??
                        new $class();
                } else {
                    /** @var AkeneoImportInterface $record */
                    $record = $class::get()->find($class::getIdentifierField(), $type === 'products' ? $akeneoItem['identifier'] : $akeneoItem['code']) ??
                        new $class();
                }

                $relatedObjectIds = $this->findRelatedObjectIds($type, $akeneoItem, $parentImportKey);
                $record->populateAkeneoData($akeneoItem, $relatedObjectIds);

                if (in_array($type, ['products', 'productModels'], true)) {
                    $this->setProductAttributes($record, $akeneoItem['values']);

                    $associationProperty = $type . 'Associations';
                    // remember relations to fill them when import of all product(models) is done
                    $this->{$associationProperty}[$record->{$class::getIdentifierField()}] = $akeneoItem['associations'];
                }

                $record->write();

                if (array_key_exists($type, $this->importParents)) {
                    $this->{$type}[$parentImportKey][$record->{$class::getIdentifierField()}] = $record;
                } else {
                    $this->{$type}[$record->{$class::getIdentifierField()}] = $record;
                }

                $this->output($record->getImportOutput());
            }
        } while ($page * $limit < $itemsCount);

        $this->deleteNotUpdated($type, $class::getIdentifierField(), $parentImportKey);
    }

    protected function setAssociations(): void
    {
        if (!empty($this->productModelsAssociations)) {
            foreach (ProductModel::get() as $productModel) {
                $this->setProductAssociations($productModel, $this->productModelsAssociations[$productModel->Code]);
            }
        }

        if (!empty($this->productsAssociations)) {
            foreach (Product::get() as $product) {
                $this->setProductAssociations($product, $this->productsAssociations[$product->SKU]);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    protected function findRelatedObjectIds(string $type, array $akeneoItem, ?string $parentCode = null): array
    {
        if ($type === 'categories' && $akeneoItem['parent'] && array_key_exists($akeneoItem['parent'], $this->categories)) {
            return ['ParentID' => $this->categories[$akeneoItem['parent']]->ID];
        }

        if ($type === 'attributes' && $akeneoItem['group'] && array_key_exists($akeneoItem['group'], $this->attributeGroups)) {
            return ['GroupID' => $this->attributeGroups[$akeneoItem['group']]->ID];
        }

        if ($type === 'attributeOptions' && $parentCode && array_key_exists($parentCode, $this->attributes)) {
            return ['AttributeID' => $this->attributes[$parentCode]->ID];
        }

        if ($type === 'variants' && $parentCode && array_key_exists($parentCode, $this->families)) {
            return ['FamilyID' => $this->families[$parentCode]->ID];
        }

        if (in_array($type, ['productModels', 'products'], true)) {
            $familyCode = $akeneoItem['family'];
            $parentCode = $akeneoItem['parent'];

            if ($type === 'productModels') {
                $variantCode = $akeneoItem['family_variant'];
                $variant = FamilyVariant::get()->filter([
                    'Code' => $variantCode,
                    'Family.Code' => $familyCode,
                ])->first();
                $ids = [
                    'FamilyVariantID' => $variant?->ID,
                ];
            } else {
                $family = Family::get()->find('Code', $familyCode);
                $ids = [
                    'FamilyID' => $family?->ID,
                ];
            }

            if ($parentCode) {
                $parent = ProductModel::get()->find('Code', $parentCode);
                $ids['ProductModelID'] = $parent?->ID;
            }

            return $ids;
        }

        return [];
    }

    protected function shouldSkip(string $type, $akeneoItem): bool
    {
        if ($type === 'categories' && $akeneoItem['parent'] && !array_key_exists($akeneoItem['parent'], $this->categories)) {
            return true;
        }

        return false;
    }

    protected function prepareImport(string $type, string $class): void
    {
        $this->{$type} = [];

        foreach ($class::get() as $record) {
            $record->Updated = false;
            $this->{$type}[$record->{$class::getIdentifierField()}] = $record;
        }
    }

    protected function prepareImportWithParent(string $type, string $class, string $parentImportKey = null): void
    {
        $this->{$type}[$parentImportKey] = [];
        $filterField = $class::getParentRelation() . '.Code';

        foreach ($class::get()->filter([
            $filterField => $parentImportKey,
        ]) as $record) {
            $record->Updated = false;
            $this->{$type}[$parentImportKey][$record->{$class::getIdentifierField()}] = $record;
        }
    }

    protected function setProductAttributes(AkeneoImportInterface $productInstance, array $attributeValues): void
    {
        foreach ($attributeValues as $attributeCode => $values) {
            $attribute = ProductAttribute::get()->find('Code', $attributeCode);
            if (!$attribute) {
                continue;
            }

            /** @var  ProductModel|Product $productInstance */
            foreach ($values as $value) {
                $akeneoLocale = $value['locale'] ?? null;
                $locale = Locale::get()->find('Code', $akeneoLocale);

                $attributeValue = $productInstance->AttributeValues()->filter([
                    'AttributeID' => $attribute->ID,
                    'LocaleID' => $locale->ID ?? 0,
                ])->first();

                $attributeValue = $attributeValue ?: new ProductAttributeValue();

                $attributeValue->AttributeID = $attribute->ID;
                $attributeValue->LocaleID = $locale?->ID;

                if ($attribute->Type === ProductAttributeValue::PIM_CATALOG_TEXTAREA_TYPE) {
                    $attributeValue->TextValue = $value['data'];
                } else {
                    $attributeValue->Value = is_array($value['data']) ? json_encode($value['data']) : $value['data'];
                }
                $productInstance->AttributeValues()->add($attributeValue);
            }
        }
    }

    protected function setProductAssociations(AkeneoImportInterface $productInstance, array $associations): void
    {
        /** @var  Product|ProductModel $productInstance */
        foreach ($productInstance->Associations() as $association) {
            $association->delete();
        }

        foreach ($associations as $associationType => $associationsPerType) {
            foreach ($associationsPerType as $relatedType => $relatedObjectKeys) {
                if (empty($relatedObjectKeys) || !array_key_exists($relatedType, $this->associationMapping)) {
                    continue;
                }

                $relatedClass = $this->associationMapping[$relatedType]['class'];
                $relatedIdentifierField = $this->associationMapping[$relatedType]['identifierField'];
                $relatedField = $this->associationMapping[$relatedType]['relatedField'];

                foreach ($relatedObjectKeys as $relatedObjectKey) {
                    $association = new ProductAssociation();
                    $association->Type = $associationType;
                    if ($productInstance instanceof ProductModel) {
                        $association->ProductModelID = $productInstance->ID;
                    } else {
                        $association->ProductID = $productInstance->ID;
                    }

                    /** @var ProductModel|Product $relatedObject */
                    $relatedObject = $relatedClass::get()->find($relatedIdentifierField, $relatedObjectKey);
                    $association->{$relatedField} = $relatedObject->ID;
                    $association->write();
                }
            }
        }
    }

    protected function importMediaFiles(): void
    {
        $this->output("Prepare import media files");
        $productMediaFiles = ProductMediaFile::get();
        foreach ($productMediaFiles as $productMediaFile) {
            $this->productMediaFiles[$productMediaFile->Code] = $productMediaFile;
        }

        $this->output("Import media files");
        $page = 0;
        $limit = 50;

        do {
            $page++;

            $mediaFiles = $this->akeneoApi->getMediaFiles($page, $limit);
            $itemsCount = $mediaFiles['items_count'];

            foreach ($mediaFiles['_embedded']['items'] as $mediaFile) {
                if (array_key_exists($mediaFile['code'], $this->productMediaFiles)) {
                    $this->output("Media file: " . $mediaFile['code'] . " - " . $mediaFile['original_filename'] . " already exists");
                    unset($this->productMediaFiles[$mediaFile['code']]);
                    continue;
                }
                $this->saveMediaFile($mediaFile);
            }
        } while ($page * $limit < $itemsCount);

        $this->output("Remove media files:");
        foreach ($this->productMediaFiles as $productMediaFile) {
            $productMediaFile->delete();
        }
    }

    protected function importMediaFile(string $code): void
    {
        $mediaFile = $this->akeneoApi->getMediaFile($code);
        $this->saveMediaFile($mediaFile);
    }

    /**
     * @throws ValidationException
     */
    protected function saveMediaFile(array $akeneoMediaFile): void
    {
        $this->output("Media file: " . $akeneoMediaFile['code'] . " - " . $akeneoMediaFile['original_filename']);

        $fileContent = $this->akeneoApi->downloadMediaFile($akeneoMediaFile['code']);

        $file = ProductMediaFile::createFromAkeneoData($akeneoMediaFile, $fileContent);

        $this->output($file->Filename . " created ");
    }

    protected function deleteNotUpdated(string $import, string $identifierField, ?string $parentCode = null): void
    {
        $records = $parentCode ? $this->{$import}[$parentCode] : $this->{$import};

        foreach ($records as $record) {
            if ($record->Updated) {
                continue;
            }

            $this->output($record->singular_name() . " deleted: " . $record->{$identifierField});
            $record->delete();
        }
    }

    protected function output($message)
    {
        if ($this->verbose) {
            echo date('d-m-Y H:i:s') . ' : ' . $message . "\n";
        }
    }

    public function setVerbose(bool $verbose): bool
    {
        return $this->verbose = $verbose;
    }

    public function getVerbose(): bool
    {
        return $this->verbose;
    }
}
