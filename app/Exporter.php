<?php
namespace app;

/**
 *
 * This class describes an exporter.
 */

class Exporter
{
    /**
     * @var array
     */
    public static $cliargs = [
        'method' => [
            'alias' => 'm',
            'help'  => 'method',
        ],
        'option' => [
            'alias' => 'o',
            'help'  => 'option',
        ],
    ];

    /**
     * @var mixed
     */
    public static $counter;

    /**
     * @var int
     */
    public static $index = 1;

    /**
     * @var string
     */
    public static $index_file = '.index.';

    /**
     * @var int
     */
    public static $index_max = 2000;

    /**
     * @var mixed
     */
    public static $index_method;

    /**
     * @var mixed
     */
    public static $index_option;

    /**
     * @var array
     */
    public static $param = [
        'category' => [
        ],
        'product'  => [
            'post_type' => 'product',
            'p'         => '',
        ],
    ];

    /**
     * { function_description }
     */
    public static function argsStackLimit(): void
    {
        if (!empty(Command::$args['stack']) && !empty(Command::$args['limit'])) {
            static::$index     = Command::$args['stack'];
            static::$index_max = Command::$args['limit'];
        } else {
            static::getLastIndexPosition();
        }
    }

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
     * { function_description }
     */
    public static function runCategoriesScraper($wc): void
    {

        $categoriesUrl = Parse::getCategoriesSitemapData(
            Config::$reg->scrape_url . Config::$reg->sitemap->category
        );

        foreach ($categoriesUrl as $targetData) {
            $curlData = Curl::get($targetData['url']);

            if (
                $curlData &&
                $curlData->content_type === 'text/html; charset=UTF-8'
            ) {
                // echo 'Parsing category: ' . $targetData['url'] . PHP_EOL;
                $categoryData = Parse::getCategoryData($curlData->body);
                $wc->saveCategory($categoryData, $targetData);
            }
        }
    }

    /**
     * @param $wc
     */
    public static function runProductsDuplicatesImagesCleaner($wc): void
    {
        $page = 1;

        do {

            $images   = [];
            $resArray = $wc->getProducts($page);
            if (count($resArray) > 0) {
                // construct relative image path
                foreach ($resArray as $item) {
                    foreach ($item->images as $img) {
                        $fileImg = Config::$reg->wordpress->path . '/' . implode('/', Parse::getUrlPathAlias($img->src));

                        if (is_file($fileImg)) {
                            $images[] = $fileImg;
                        }
                    }
                }

                foreach ($images as $img) {
                    $md5Img  = md5(file_get_contents($img));
                    $fileDir = dirname($img);
                    foreach (scandir($fileDir) as $key => $dirFile) {
                        $dupFile = $fileDir . '/' . $dirFile;

                        if (is_file($dupFile)) {
                            // var_dump($dupFile);
                            $md5DirFile = md5(file_get_contents($dupFile));
                            if ($md5DirFile === $md5Img && $dupFile != $img) {
                                View::cli('duplicate image: ' . $dupFile . PHP_EOL . 'product image: ' . $img);
                                unlink($dupFile);
                            }
                        }
                    }
                }

                View::cli('page: ' . $page);

            } else {
                $page = 0;
            }

            $page += 1;

        } while ($page > 0);
    }

    /**
     * @param $wc
     */
    public static function runProductsScraper($wc): void
    {
        static::argsStackLimit();

        foreach (range(static::$index, static::$index_max) as $key => $index) {
            static::$index                                = $index;
            static::$param[Command::$args['method']]['p'] = $index;
            $targetUrl                                    = Config::$reg->scrape_url . '?' . http_build_query(static::$param[Command::$args['method']]);
            $curlData                                     = Curl::get($targetUrl);

            if (Command::$args['verbose']) {
                View::cli($targetUrl);
            }

            if (
                $curlData &&
                $curlData->content_type === 'text/html; charset=UTF-8'
            ) {
                $parsedData = Parse::getProductData($curlData->body);
                $productId  = $wc->saveProduct($parsedData, $targetUrl);
                if ($productId > 0) {
                    View::cli(
                        'Product added! ' . Config::$reg->localsite_url .
                        'wp-admin/post.php?post=' . $productId . '&action=edit' . PHP_EOL .
                        $targetUrl
                    );
                } else {
                    View::cli('Product not added! ' . $targetUrl);
                }
            }

            static::setLastIndexPosition();
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
}
