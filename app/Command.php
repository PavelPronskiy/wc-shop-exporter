<?php
namespace app;

use CliArgs\CliArgs;

/**
 * This class describes an exporter.
 */
class Command
{
    /**
     * @var array
     */
    public static $args = [];

    /**
     * @var array
     */
    public static $cliargs = [
        'method'  => [
            'alias' => 'm',
            'help'  => 'method',
        ],
        'option'  => [
            'alias' => 'o',
            'help'  => 'option',
        ],
        'stack'   => [
            'alias' => 's',
            'help'  => 'stack size',
        ],
        'limit'   => [
            'alias' => 'l',
            'help'  => 'limit size',
        ],
        'verbose' => [
            'alias'  => 'v',
            'help'   => 'verbose',
            'filter' => 'verbose',
        ],
    ];

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $wc           = new WooCommerce();
        $CliArgs      = new CliArgs(static::$cliargs);
        static::$args = [
            'method'  => $CliArgs->getArg('m'),
            'option'  => $CliArgs->getArg('o'),
            'stack'   => $CliArgs->getArg('s'),
            'limit'   => $CliArgs->getArg('l'),
            'verbose' => $CliArgs->isFlagExist('v'),
        ];

        $this->run($wc);
    }

    /**
     * { function_description }
     *
     * @param <type> $wc { parameter_description }
     */
    public function run($client)
    {

        switch (static::$args['method'])
        {
            case 'category':
                Exporter::runCategoriesScraper($client);
                break;

            case 'product':
                if (!empty(static::$args['option']))
                {
                    View::json($client->getProductByID(static::$args['option']));
                }
                else
                {
                    Exporter::runProductsScraper($client);
                }

                break;

            case 'products/dup/images':
                Exporter::runProductsDuplicatesImagesCleaner($client);
                break;

            case 'product/attributes':
            case 'products/attributes':
                View::json($client->getProductAttributes());
                break;

            case 'products/variations':
                if (!empty(static::$args['option']))
                {
                    View::json($client->getProductVariations(static::$args['option']));
                }

                break;

            case 'boost/cache':
                $boostPages = BoostCachePages::boostPagesFromSitemap();
                var_dump($boostPages);

                break;

            default:
                break;
        }
    }
}
