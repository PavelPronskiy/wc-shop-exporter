<?php

namespace app;

/**
 *
 * This class describes an exporter.
 */

class Exporter
{
    public static $index = 1;
    public static $index_file = '.index.';
    public static $index_max = 2000;
    public static $index_method;
    public static $index_option;
    public static $counter;


    public static $cliargs = [
        'method' => [
            'alias' => 'm',
            'help' => 'method',
        ],
        'option' => [
            'alias' => 'o',
            'help' => 'option',
        ],
    ];

    public static $param = [
        'category' => [
        ],
        'product' => [
            'post_type' => 'product',
            'p' => ''
        ]
    ];


    /**
     * Gets the last index position.
     */
    public static function getLastIndexPosition()
    {
        if (file_exists(static::$index_file . Command::$args['method'])) {
            static::$index = file_get_contents(static::$index_file . Command::$args['method']);
        } else {
            file_put_contents(static::$index_file . Command::$args['method'], static::$index);
        }
    }

    /**
     * Sets the last index position.
     */
    public static function setLastIndexPosition(): void
    {
        if (file_exists(static::$index_file . Command::$args['method'])) {
            file_put_contents(static::$index_file . Command::$args['method'], static::$index);
        }
    }



    /**
     * { function_description }
     */
    public static function runCategoriesScraper($wc): void
    {
        $sitemapCategories = Curl::get(Config::$reg->scrape_url . '/' . Config::$reg->sitemap->category);
        $categoriesUrl = Parse::getCategoriesXMLData($sitemapCategories->body);

        foreach ($categoriesUrl as $targetUrl) {
            $curlData = Curl::get($targetUrl);
            switch ($curlData->content_type) {
                case 'text/html; charset=UTF-8':
                    echo 'Parsing category: ' . $targetUrl . PHP_EOL;
                    $categoryData = Parse::getCategoryData($curlData->body);
                    $wc->saveCategory($categoryData, $targetUrl);

                    break;

                default:
                    break;
            }
        }
    }

    /**
     * { function_description }
     */
    public static function argsStackLimit(): void
    {
        if (!empty(Command::$args['stack']) && !empty(Command::$args['limit'])) {
            static::$index = Command::$args['stack'];
            static::$index_max = Command::$args['limit'];
        } else {
            static::getLastIndexPosition();
        }
    }

    public static function runProductsScraper($wc): void
    {
        static::argsStackLimit();

        foreach (range(static::$index, static::$index_max) as $key => $index) {
            static::$index = $index;
            static::$param[Command::$args['method']]['p'] = $index;
            $targetUrl = Config::$reg->scrape_url . '?' . http_build_query(static::$param[Command::$args['method']]);
            $curlData = Curl::get($targetUrl);

            // View::cli($targetUrl);

            if (
                $curlData &&
                $curlData->content_type === 'text/html; charset=UTF-8'
            ) {
                $parsedData = Parse::getProductData($curlData->body);
                $productId = $wc->saveProduct($parsedData, $targetUrl);
                if ($productId > 0) {
                    View::cli(
                        'Product added! ' . Config::$reg->localsite_url .
                        'wp-admin/post.php?post=' . $productId . '&action=edit'
                    );
                } else {
                    View::cli('Product not added! ' . $targetUrl);
                }
            }

            static::setLastIndexPosition();
        }
    }
}
