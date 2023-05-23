<?php

namespace app;

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

/**
 *
 * This class describes an exporter.
 */
class WooCommerce
{
    public $client;

    public function __construct()
    {
        $this->client = new Client(
            'https://rampitomnik.pronskiy.ru', // Укажите URL вашего сайта WooCommerce
            'ck_886c3c0041f15c133b0d5bab64a0253bbed99731',
            'cs_6f3e642f3ebf1e5d31cb156210b10bd9861ea0e2',
            [
            'version' => 'wc/v3',
            ]
        );
    }

    public function getWoocommerceCategories()
    {
        return $this->client->get('products/categories', ['per_page' => 100]);
    }

    public function getWoocommerceCategoriesSlugNames(
        array $categories,
        array $array = []
    ): array {
        foreach ($categories as $category) {
            $array[$category->id] = [
                'slug' => $category->slug,
                'name' => $category->name
            ];
        }

        return $array;
    }

    public function getWoocommerceCategoryIDBySlugName(
        array $categoriesNames,
        array $productCategories,
        array $array = []
    ): array {
        foreach ($categoriesNames as $categoryId => $item) {
            foreach ($productCategories as $category) {
                if ($category['alias'] === $item['slug']) {
                    $array[] = [ 'id' => $categoryId ];
                }
            }
        }

        return $array;
    }

    public function getWoocommerceCategoryIDBySlug(
        array $categories,
        string $categorySlug,
        $object = []
    ): object {
        foreach ($categories as $categoryId => $item) {
            if ($item['slug'] === $categorySlug) {
                return (object) [
                    'id' => $categoryId,
                    'name' => $item['name']
                ];
            }
        }

        return (object) $object;
    }


    public function saveCategory(
        object $data,
        string $targetUrl,
        array $save = []
    ) {
        $categories = $this->getWoocommerceCategories();
        $categoriesNames = $this->getWoocommerceCategoriesSlugNames($categories);

        $endParentCategory = end($data->parents);
        $parentCategoryData = $this->getWoocommerceCategoryIDBySlug($categoriesNames, $endParentCategory);

        if (isset($parentCategoryData->id)) {
            $save = [
                'name' => $data->name,
                'slug' =>  $data->slug,
                'parent' => $parentCategoryData->id,
                'display' => 'default',
                'description' => $data->description
            ];

            if (!empty($data->image)) {
                $save['image'] = [
                    'src' => $data->image
                ];
            }

            if (!in_array($data->slug, array_column($categoriesNames, 'slug'))) {
                try {
                    // Отправка запроса для создания новой категории
                    $response = $this->client->post('products/categories', $save);
                } catch (HttpClientException $e) {
                    echo 'Error: ' . $targetUrl . PHP_EOL;
                    // var_dump($e);
                }
            }
        } else {
            var_dump('not found: ' . $endParentCategory);
        }
    }


    /**
     * Saves a product.
     *
     * @param      object  $data       The data
     * @param      string  $targetUrl  The target url
     * @param      array   $save       The save
     */
    public function saveProduct(
        object $data,
        string $targetUrl,
        array $save = [],
        int $result = 0
    ) {

        // attributes
        // возраст id 11
        // услуги id 12
        //

        if (count($data->categories) > 1) {
            $categories = $this->getWoocommerceCategories();
            $categoriesNames = $this->getWoocommerceCategoriesSlugNames($categories);
            $categoriesData = $this->getWoocommerceCategoryIDBySlugName($categoriesNames, $data->categories);
            $productAttributes = $this->getProductAttributes();

            $save = [
                'variations' => $this->setProductVariations($data->attributes),
                'product' => [
                    'name' => $data->name,
                    'type' => 'variable',
                    'price' => $data->price,
                    'description' => $data->description,
                    'images' =>  [
                        [
                            'src' => $data->images->src
                        ]
                    ],
                    'purchasable' => true,
                    'shipping_required' => true,
                    'shipping_taxable' => true,
                    'stock_status' => 'instock',
                    'status' => 'publish',
                    'catalog_visibility' => 'visible',
                    'categories' => $categoriesData,
                    'attributes' => $this->setProductAttributes($data->attributes),
                    // 'default_attributes' => $this->setProductDefaultAttributes(),
                    // 'variations' => $this->setProductVariations($data->attributes)
                ]
            ];

            // var_dump($save['product']['attributes']);
            // exit;

            try {
                // Отправка запроса для создания нового товара
                $resProduct = $this->client->post('products', $save['product']);

                if (isset($resProduct->id)) {
                    // Отправка запроса для создания вариаций товара
                    // var_dump($save['variations']);
                    $resVariations = $this->client->post(
                        'products/' . $resProduct->id . '/variations/batch',
                        [
                            'create' => $save['variations']
                        ]
                    );

                    $result = (int) $resProduct->id;
                }
            } catch (HttpClientException $e) {
                echo 'Error: ' . $targetUrl . PHP_EOL;
                var_dump($e);
            }
        }

        return $result;
    }


    /**
     * Gets the product attributes.
     *
     * @param      <type>  $variations  The variations
     * @return     array   The product attributes.
     */
    public function getProductAttributes()
    {
        return $this->client->get('products/attributes');
    }

    public function getProductVariations($productId)
    {
        return $this->client->get('products/' . $productId . '/variations');
    }


    /**
     * Sets the product attributes.
     *
     * @param      array  $dataAttributes     The data attributes
     * @param      array  $productAttributes  The product attributes
     *
     * @return     array  ( description_of_the_return_value )
     */
    public function setProductAttributes(
        array $dataAttributes,
        array $array = []
    ) {
        $array = [
            [
                'id' => 11,
                'position' => 0,
                'visible' => true,
                'variation' => true,
                'options' => []
            ],
            [
                'id' => 12,
                'position' => 0,
                'visible' => true,
                'variation' => true,
                'options' => []
            ],
        ];

        foreach ($dataAttributes as $dataAttrA) {
            foreach ($dataAttrA as $dataAttrB) {
                if ($dataAttrB['key'] === 'attribute_pa_возраст') {
                    $array[0]['options'][] = $dataAttrB['name'];
                } elseif ($dataAttrB['key'] === 'attribute_pa_услуги') {
                    $array[1]['options'][] = $dataAttrB['name'];
                }
            }
        }

        for ($i = 0; $i < count($array); $i++) {
            $array[$i]['options'] = array_unique($array[$i]['options']);
        }

        // var_dump($array);
        // exit;

        return $array;
    }

    public function setProductDefaultAttributes()
    {
        return (array) Config::$reg->productDefaultAttributes;
    }


    /**
     * Sets the product attribute variations.
     *
     * @param      array  $data   The data
     * @param      array  $array  The array
     * @param      array  $keys   The keys
     *
     * @return     array  ( description_of_the_return_value )
     */
    public function setProductAttributeVariations(
        array $data,
        array $array = [],
        array $keys = [ 11 => 'attribute_pa_возраст', 12 => 'attribute_pa_услуги']
    ) {
        foreach ($data as $variation) {
            $array[] = [
                'id' => array_keys($keys)[array_search($variation['key'], array_values($keys))],
                'option' => Parse::replProdAttrsNamesToSlugOpts($variation['name'])
            ];
        }

        return $array;
    }

    public function setProductVariations(
        array $dataAttributes,
        array $array = []
    ) {
        foreach ($dataAttributes as $keyA => $dataAttrA) {
            $array[$keyA] = [];
            $array[$keyA]['regular_price'] = $dataAttrA[0]['display_price'];
            $array[$keyA]['attributes'] = static::setProductAttributeVariations($dataAttrA);

            // foreach ($dataAttrA as $keyB => $dataAttrB) {
                // $array[$keyA]['regular_price'] = $dataAttrB['regular_price'];
            // }
                // if (in_array($dataAttrB['key'], array_values($keys))) {
                    // $array[] = static::setProductAttributeVariations(array_keys($keys), $dataAttrB);
                // } elseif ($dataAttrB['key'] === 'attribute_pa_услуги') {
                    // $array[$keyA] = static::setProductAttributeVariations(12, $dataAttrB);
                // }
        }

        return $array;
    }


    /**
     * Gets the product by id.
     *
     * @param      int   $id     The identifier
     */
    public function getProductByID(
        int $id = 1434
    ) {
        return $this->client->get("products/$id");
    }
}
