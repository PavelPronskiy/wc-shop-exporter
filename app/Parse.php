<?php

namespace app;

use Masterminds\HTML5;

/**
 * This class describes a parse.php
 */
class Parse
{
    public static $options = [ 'disable_html_ns' => true ];
    public static $target_url;
    public static $dom;
    public static $xml;

    /**
     * [getElementsByClass description]
     * @param  [type] &$parentNode [description]
     * @param  [type] $tagName     [description]
     * @param  [type] $className   [description]
     * @return [type]              [description]
     */
    public static function getElementsByClass(&$parentNode, $tagName, $className)
    {
        $nodes = [];

        $childNodeList = $parentNode->getElementsByTagName($tagName);
        for ($i = 0; $i < $childNodeList->length; $i++) {
            $temp = $childNodeList->item($i);
            if (stripos($temp->getAttribute('class'), $className) !== false) {
                $nodes[] = $temp;
            }
        }

        return $nodes;
    }

    /**
     * [getProductData description]
     *
     * @param      string  $body   [description]
     * @return     [type]  [description]
     */
    public static function getProductData(
        ?string $body
    ): object {
        // var_dump($body);

        static::loadHTML($body);
        $categories = static::getProductCategories();
        $variations = static::getProductVariations();

        return (object) [
            'page_title' => static::getPageTitle(),
            'categories' => $categories,
            'name' => static::getProductTitle(),
            'description' => static::getProductDescription(),
            'price' => static::getProductPrice($variations),
            'attributes' => static::getProductAttributes($variations),
            'images' => static::getProductImage($variations)
        ];
    }


    /**
     * Gets the categories xml data.
     *
     * @param      null|string  $xml    The xml
     * @param      array        $array  The array
     *
     * @return     array        The categories xml data.
     */
    public static function getCategoriesXMLData(
        ?string $xml,
        array $array = []
    ) {

        static::loadXML($xml);

        foreach (static::$xml->getElementsByTagName('loc') as $loc) {
            $array[] = $loc->textContent;
        }

        return $array;
    }

    /**
     * Gets the category name.
     *
     * @return     <type>  The category name.
     */
    public static function getCategoryName()
    {
        foreach (static::$dom->getElementsByTagName('h1') as $node) {
            return $node->nodeValue;
        }
    }


    /**
     * Gets the product title.
     *
     * @return     <type>  The product title.
     */
    public static function getProductTitle()
    {
        foreach (static::$dom->getElementsByTagName('h1') as $node) {
            return $node->nodeValue;
        }
    }

    /**
     * Gets the category slug.
     *
     * @return     <type>  The category slug.
     */
    public static function getCategorySlug()
    {
        foreach (static::$dom->getElementsByTagName('link') as $node) {
            if ($node->getAttribute('rel') === 'canonical') {
                $href = static::getUrlPathAlias($node->getAttribute('href'));
                return $href[count($href) - 1];
            }
        }
    }


    /**
     * Gets the category parents.
     *
     * @param      array  $href   The href
     *
     * @return     array  The category parents.
     */
    public static function getCategoryParents(
        array $href = []
    ): array {
        foreach (static::$dom->getElementsByTagName('link') as $node) {
            if ($node->getAttribute('rel') === 'canonical') {
                $href = static::getUrlPathAlias($node->getAttribute('href'));
                // return $href[count($href) - 1];
            }
        }

        if (count($href) > 0) {
            unset($href[0], $href[count($href)]);
        } else {
            return [];
        }

        // var_dump($href);
        return $href;
    }

    /**
     * Gets the category data.
     *
     * @param      string  $body   The body
     *
     * @return     <type>  The category data.
     */
    public static function getCategoryData(
        string $body
    ) {

        static::loadHTML($body);
        $parents = static::getCategoryParents();
        $slug = static::getCategorySlug();
        $name = static::getCategoryName();

        return (object) [
        'name' => $name,
        'slug' => $slug,
        'parents' => $parents,
        'description' => static::getCategoryDescription(),
        'image' => static::getCategoryImage($parents, $slug),
        ];
    }

    /**
     * [changeBaseHref description]
     * @return     [type]  [description]
     */
    public static function getPageTitle()
    {
        foreach (static::$dom->getElementsByTagName('title') as $node) {
            return $node->nodeValue;
        }
    }

    /**
     * Gets the parent category image.
     *
     * @param      string  $slug   The slug
     * @param      array   $array  The array
     *
     * @return     <type>  The parent category image.
     */
    public static function getParentCategoryImage(
        string $slug,
        array $array = []
    ) {
        foreach (static::$dom->getElementsByTagName('a') as $node) {
            if ($node->getAttribute('class') === 'product-thumbnail') {
                if (in_array($slug, static::getUrlPathAlias($node->getAttribute('href')))) {
                    foreach ($node->getElementsByTagName('img') as $img) {
                        $src = explode(' ', $img->getAttribute('srcset'));
                        $array[] = $src[0];
                    }
                }
            }
        }

        return count($array) > 0 ? $array[0] : [];
    }
    /**
     * [changeBaseHref description]
     * @return     [type]  [description]
     */
    public static function getCategoryImage(
        array $parents,
        string $slug
    ) {
        $parentUrl = Config::$reg->scrape_url . 'product-category/' . implode('/', $parents) . '/';
        $parentData = Curl::get($parentUrl);
        // var_dump($parentUrl);

        if (!empty($parentData->body)) {
            static::loadHTML($parentData->body);
            return static::getParentCategoryImage($slug);
        } else {
            return '';
        }
    }

    /**
     * [changeBaseHref description]
     * @return     [type]  [description]
     */
    public static function getCategoryDescription(): string
    {
        $dom = new HTML5(static::$options);
        foreach (static::$dom->getElementsByTagName('div') as $node) {
            if ($node->getAttribute('class') === 'section-text') {
                return $dom->saveHTML($node->childNodes);
            }
        }

        return '';
    }

    /**
     * { function_description }
     *
     * @param      <type>  $item   The item
     * @param      array   $array  The array
     *
     * @return     array   ( description_of_the_return_value )
     */
    public static function mapAttributes(
        $item,
        array $array = []
    ) {
        foreach ($item->attributes as $key => $attr) {
            $array[] = [
                'key' => urldecode($key),
                'display_price' => $item->display_price,
                'name' => static::replaceProductAttributeNames(
                    urldecode($attr)
                )
            ];
        }

        return $array;
    }

    /**
     * { function_description }
     *
     * @param      string  $attr   The attribute
     *
     * @return     string  ( description_of_the_return_value )
     */
    public static function replaceProductAttributeNames(
        string $attr
    ): string {
        foreach ((array) Config::$reg->attributeReplaces as $str) {
            $attr = str_replace($str[0], $str[1], $attr);
        }

        return $attr;
    }

    /**
     * { function_description }
     *
     * @param      string  $attr   The attribute
     *
     * @return     string  ( description_of_the_return_value )
     */
    public static function replProdAttrsNamesToSlugOpts(
        string $attr
    ): string {
        foreach ((array) Config::$reg->attributeOptionsNamesToSlugsReplaces as $str) {
            $attr = str_replace($str[0], $str[1], $attr);
        }

        return $attr;
    }

    /**
     * Gets the product attributes.
     *
     * @param      array  $variations  The variations
     * @param      array  $array       The array
     *
     * @return     array  The product attributes.
     */
    public static function getProductAttributes(
        array $variations,
        array $array = []
    ) {
        foreach ($variations as $item) {
            $array[] = static::mapAttributes($item);
        }

        return $array;
    }


    /**
     * Gets the product image.
     *
     * @param      array   $variations  The variations
     * @param      array   $array       The array
     *
     * @return     <type>  The product image.
     */
    public static function getProductImage(
        array $variations,
        $array = []
    ) {
        foreach ($variations as $item) {
            return $item->image;
        }

        return (object) $array;
    }

    /**
     * Gets the product price.
     *
     * @param      <type>  $variations  The variations
     * @return     <type>  The product price.
     */
    public static function getProductPrice($variations)
    {
        $array = [];
        foreach ($variations as $key => $item) {
            $array[$key] = $item->display_price;
        }


        return count($array) > 0 ? min($array) : 0;
    }


    /**
     * Gets the product variations.
     *
     * @return     array  The product variations.
     */
    public static function getProductVariations(): array
    {
        foreach (static::$dom->getElementsByTagName('form') as $node) {
            if ($node->getAttribute('class') === 'variations_form cart') {
                $product = $node->getAttribute('data-product_variations');
                if (!empty($product)) {
                    return json_decode($product);
                }
            }
        }

        return [];
    }

    /**
     * [textReplaces description]
     * @param  [type] $text [description]
     * @return [type]       [description]
     */
    public static function textReplaces($text): string
    {
        foreach (Config::$reg->replaces as $replace) {
            $text = str_replace($replace->target, $replace->modify, $text);
        }

        return $text;
    }

    /**
     * Gets the product description.
     *
     * @return     string  The product description.
     */
    public static function getProductDescription(): string
    {
        $dom = new HTML5(static::$options);

        foreach (static::$dom->getElementsByTagName('div') as $key => $node) {
            if ($node->getAttribute('class') === 'goodsCard__descript') {
                foreach ($node->getElementsByTagName('p') as $key => $nodeP) {
                    $nodeP->textContent = static::textReplaces($nodeP->textContent);
                }

                foreach ($node->getElementsByTagName('div') as $key => $nodeDiv) {
                    if ($nodeDiv->getAttribute('class') === 'title') {
                        $node->removeChild($nodeDiv);
                    }
                }
                return $dom->saveHTML($node->childNodes);
            }
        }

        return '';
    }

    /**
     * [getProductCategories description]
     * @return [type] [description]
     */
    public static function getProductCategories(): array
    {
        $array = [];
        foreach (static::$dom->getElementsByTagName('nav') as $key => $node) {
            foreach ($node->getElementsByTagName('a') as $key => $a) {
                $hrefs = self::getUrlPathAlias($a->getAttribute('href'));
                if (count($hrefs) > 0) {
                    $array[] = [
                    'alias' => $hrefs[count($hrefs) - 1],
                    'category' => $a->nodeValue
                    ];
                }
            }
        }

        return $array;
    }

    /**
     * Gets the url path alias.
     *
     * @param      <type>  $url    The url
     * @return     <type>  The url path alias.
     */
    public static function getUrlPathAlias($url)
    {
        return array_values(
            array_filter(
                explode('/', parse_url(urldecode($url), PHP_URL_PATH))
            )
        );
    }

    /**
     * Renders the object.
     *
     * @return     string  ( description_of_the_return_value )
     */
    public static function render(): string
    {
        return static::$dom->saveHTML();
    }

    /**
     * Loads a html.
     *
     * @param      string  $html   The html
     */
    public static function loadHTML(string $html): void
    {
        $dom = new HTML5(static::$options);
        static::$dom = $dom->loadHTML($html);
    }

    /**
     * Loads a xml.
     *
     * @param      \|string  $xml    The xml
     */
    public static function loadXML(string $xml): void
    {
        static::$xml = new \DOMDocument();
        static::$xml->loadXML($xml);
    }
}
