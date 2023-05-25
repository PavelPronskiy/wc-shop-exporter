<?php
namespace app;

use vipnytt\SitemapParser;

/**
 * This class describes a parse.php
 */
class BoostCachePages
{
    public static function boostPagesFromSitemap()
    {
        // Config::$reg->scrape_url . Config::$reg->sitemap->category
        $parser = new SitemapParser();
        $parser->parse(Config::$reg->scrape_url . 'sitemap_index.xml');
        foreach ($parser->getSitemaps() as $urlSitemaps => $sitemaps)
        {
            foreach ($sitemaps as $tag => $sitemapCatUrl)
            {
                if ($tag === 'loc')
                {
                    $parser->parse($sitemapCatUrl);
                    foreach ($parser->getURLs() as $pageUrl => $pageItem)
                    {

                        var_dump($pageUrl);
                    }
                }
            }
        }
    }
}
