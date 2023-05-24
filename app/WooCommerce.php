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
    /**
     * @var mixed
     */
    public $client;

    public function __construct()
    {
        $this->client = new Client(
            Config::$reg->localsite_url,
            Config::$reg->woocommerce->client,
            Config::$reg->woocommerce->secret,
            (array) Config::$reg->woocommerce->options
        );
    }

    /**
     * Gets the product attributes.
     *
     * @param  <type> $variations The variations
     * @return array  The product attributes.
     */
    public function getProductAttributes()
    {
        return $this->client->get('products/attributes');
    }

    /**
     * Gets the product by productId.
     *
     * @param int $productId The identifier
     */
    public function getProductByID(
        int $productId = 1434
    ) {
        return $this->client->get("products/$productId");
    }

    /**
     * @param  $slug
     * @return mixed
     */
    public function getProductBySlug($slug)
    {
        return $this->client->get('products', [
            'slug' => $slug,
        ]);
    }

    /**
     * @param  $productId
     * @return mixed
     */
    public function getProductVariations($productId)
    {
        return $this->client->get('products/' . $productId . '/variations');
    }

    /**
     * @return mixed
     */
    public function getWoocommerceCategories()
    {
        return $this->client->get('products/categories', ['per_page' => 100]);
    }

    /**
     * @param  array   $categories
     * @param  array   $array
     * @return mixed
     */
    public function getWoocommerceCategoriesSlugNames(
        array $categories,
        array $array = []
    ): array
    {
        foreach ($categories as $category) {
            $array[$category->id] = [
                'slug' => $category->slug,
                'name' => $category->name,
            ];
        }

        return $array;
    }

    /**
     * @param  string  $slug
     * @return mixed
     */
    public function getWoocommerceCategoryDataBySlug(
        string $slug
    ) {
        return $this->client->get('products/categories', [
            'slug'    => $slug,
            'orderby' => 'slug',
        ]);
    }

    /**
     * @param array  $categories
     * @param string $slug
     * @param array  $object
     */
    public function getWoocommerceCategoryIDBySlug(
        array $categories,
        array $parents,
              $object = []
    ): object {
        // var_dump($slug);

        if (count($parents) === 0) {
            return (object) $object;
        }

        $slug = end($parents);

        $parentCatDataArray = static::getWoocommerceCategoryDataBySlug($slug);

        if (count($parentCatDataArray) > 0) {
            $parentCatData = $parentCatDataArray[0];

            return (object) [
                'id'   => $parentCatData->id,
                'name' => $parentCatData->name,
            ];
        }
        // var_dump(static::getWoocommerceCategoryDataBySlug($slug));
        // exit;
        /*foreach ($categories as $categoryId => $item) {
            if ($item['slug'] === $slug) {

                return (object) [
                    'id'   => $categoryId,
                    'name' => $item['name'],
                ];
            }
        }*/

        return (object) $object;
    }

    /**
     * @param  array   $categoriesNames
     * @param  array   $productCategories
     * @param  array   $array
     * @return mixed
     */
    public function getWoocommerceCategoryIDBySlugName(
        // array $categoriesNames,
        array $productCategories,
        array $array = []
    ): array
    {

        // var_dump($categoriesNames);
        // foreach ($categoriesNames as $categoryId => $item) {
        // foreach ($productCategories as $category) {
        $catEnd           = end($productCategories);
        $getExistCategory = static::getWoocommerceCategoryDataBySlug($catEnd['alias']);
        $getExistCategory = count($getExistCategory) > 0 ? (array) $getExistCategory[0] : [];
        if (isset($getExistCategory['slug']) && $catEnd['alias'] === $getExistCategory['slug']) {
            // $array[] = [
            // 'id' => $getExistCategory['id'],
            // 'name' => $getExistCategory['name'],
            // ];
            $array = [
                'id' => $getExistCategory['id'],
            ];
        }

        return $array;
    }

    /**
     * @param object $data
     * @param string $targetData
     * @param array  $save
     */
    public function saveCategory(
        object $data,
        array  $targetData,
        array  $save = []
    ) {
        $categories      = $this->getWoocommerceCategories();
        $categoriesNames = $this->getWoocommerceCategoriesSlugNames($categories);

        // $endParentCategory  = end($data->parents);
        $parentCategoryData = $this->getWoocommerceCategoryIDBySlug($categoriesNames, $data->parents);

        /**
         * category_1
         *    \_ category_2
         *          \_ category_3
         */

        $save = [
            'name'        => $data->name,
            'slug'        => $data->slug,
            'parent'      => isset($parentCategoryData->id) ? $parentCategoryData->id : 0,
            'display'     => 'default',
            'description' => $data->description,
            'image'       => [],
        ];

        if (!empty($data->image)) {
            $save['image']['src'] = $data->image;
        }
        if (!empty($targetData['image'])) {
            $save['image']['src'] = $targetData['image'];
        }

        $getExistCategory = static::getWoocommerceCategoryDataBySlug($data->slug);
        // var_dump(array_column($categoriesNames, 'slug'));
        if (count($getExistCategory) === 0) {
            // Отправка запроса для создания новой категории
            $response = $this->client->post('products/categories', $save);

            if (isset($response->id)) {

                $parentsMsg = count($data->parents) > 0 ? (string) implode('/', $data->parents) . '/' : '';

                View::cli('Created category: ' . $parentsMsg . $data->slug);
            } else {
                View::cli('Error: ' . $targetData['url']);
            }
        } else {
            $categ = $getExistCategory[0];
            View::cli('Category exists: ' . $categ->slug);
        }

        // category_1
        // if (count($data->parents) === 0) {
        // var_dump(count($data->parents));
        // }

        /*if (!isset($parentCategoryData->id)) {

            // create parent category

            var_dump($data);

        } else {

        }
*/
        // if (isset($parentCategoryData->id)) {
/*        $save = [
            'name'        => $data->name,
            'slug'        => $data->slug,
            'parent'      => $parentCategoryData->id,
            'display'     => 'default',
            'description' => $data->description,
        ];

        if (!empty($data->image)) {
            $save['image'] = [
                'src' => $data->image,
            ];
        }

        if (!in_array($data->slug, array_column($categoriesNames, 'slug'))) {
            try {
                // Отправка запроса для создания новой категории
                // $response = $this->client->post('products/categories', $save);
            } catch (HttpClientException $e) {
                echo 'Error: ' . $targetUrl . PHP_EOL;
                // var_dump($e);
            }
        }*/
/*        } else {
            var_dump('not found: ' . $endParentCategory);
        }*/
    }

    /**
     * Saves a product.
     *
     * @param object $data      The data
     * @param string $targetUrl The target url
     * @param array  $save      The save
     */
    public function saveProduct(
        object $data,
        string $targetUrl,
        array  $save = [],
        int    $result = 0
    ) {

        // attributes
        // возраст id 11
        // услуги id 12
        //
        if (count($data->categories) === 0) {
            return false;
        }

        $categoriesData = $this->getWoocommerceCategoryIDBySlugName($data->categories);

        $productAttributes = $this->setProductAttributes($data->attributes);

        $save = [
            'variations' => $this->setProductVariations($data->attributes),
            'product'    => [
                'name'               => $data->name,
                'type'               => 'variable',
                'price'              => $data->price,
                'description'        => $data->description,
                'images'             => [
                    [
                        'src' => $data->images->src,
                    ],
                ],
                'purchasable'        => true,
                'shipping_required'  => true,
                'shipping_taxable'   => true,
                'stock_status'       => 'instock',
                'status'             => 'publish',
                'catalog_visibility' => 'visible',
                'categories'         => [
                    $categoriesData,
                ],
                'attributes'         => $productAttributes,
                'default_attributes' => $this->setProductDefaultAttributes($productAttributes),
                // 'variations' => $this->setProductVariations($data->attributes)
            ],
        ];

        try {
            if (count(static::getProductBySlug($data->slug)) === 0) {
                // Отправка запроса для создания нового товара
                $resProduct = $this->client->post('products', $save['product']);

                if (isset($resProduct->id)) {
                    // Отправка запроса для создания вариаций товара
                    $resVariations = $this->client->post(
                        'products/' . $resProduct->id . '/variations/batch',
                        [
                            'create' => $save['variations'],
                        ]
                    );

                    $result = (int) $resProduct->id;
                }
            }
        } catch (HttpClientException $e) {
            echo 'Error: ' . $targetUrl . PHP_EOL;
            var_dump($e);
        }
        // }

        return $result;
    }

    /**
     * Sets the product attribute variations.
     *
     * @param  array $data  The data
     * @param  array $array The array
     * @param  array $keys  The keys
     * @return array ( description_of_the_return_value )
     */
    public function setProductAttributeVariations(
        array $data,
        array $array = [],
        array $keys = [11 => 'attribute_pa_возраст', 12 => 'attribute_pa_услуги']
    ) {
        foreach ($data as $variation) {
            $array[] = [
                'id'     => array_keys($keys)[array_search($variation['key'], array_values($keys))],
                'option' => Parse::replProdAttrsNamesToSlugOpts($variation['name']),
            ];
        }

        return $array;
    }

    /**
     * Sets the product attributes.
     *
     * @param  array $dataAttributes    The data attributes
     * @param  array $productAttributes The product attributes
     * @return array ( description_of_the_return_value )
     */
    public function setProductAttributes(
        array $dataAttributes,
        array $array = []
    ) {
        $array = [
            [
                'id'        => 11,
                'position'  => 0,
                'visible'   => true,
                'variation' => true,
                'options'   => [],
            ],
            [
                'id'        => 12,
                'position'  => 0,
                'visible'   => true,
                'variation' => true,
                'options'   => [],
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
            $array[$i]['options'] = static::sortProductAttributes(
                array_unique($array[$i]['options'])
            );
        }

        // var_dump($array);
        // exit;

        return $array;
    }

    /**
     * @param $attr
     */
    public function setProductDefaultAttributes(
        array $attr,
        array $array = []
    ) {
        foreach ($attr as $key => $value) {
            $array[] = [
                'id'     => $value['id'],
                'option' => $value['options'][0],
            ];
        }

        return $array;
    }

    /**
     * @param  array   $dataAttributes
     * @param  array   $array
     * @return mixed
     */
    public function setProductVariations(
        array $dataAttributes,
        array $array = []
    ) {
        foreach ($dataAttributes as $keyA => $dataAttrA) {
            $array[$keyA]                  = [];
            $array[$keyA]['regular_price'] = $dataAttrA[0]['display_price'];
            $array[$keyA]['attributes']    = static::setProductAttributeVariations($dataAttrA);

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
     * @param  array   $attr
     * @return mixed
     */
    public function sortProductAttributes(
        array $attr
    ) {

        usort($attr, function (
            $a,
            $b
        ) {
            $ae = explode(' ', $a);
            $be = explode(' ', $b);

            return (int) $ae[0] - (int) $be[0];
        });

        return $attr;
    }
}
