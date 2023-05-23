<?php

namespace app;

use CliArgs\CliArgs;

/**
 *
 * This class describes an exporter.
 */
class Command
{
    public static $args = [];
    public static $cliargs = [
        'method' => [
            'alias' => 'm',
            'help' => 'method',
        ],
        'option' => [
            'alias' => 'o',
            'help' => 'option',
        ],
        'stack' => [
            'alias' => 's',
            'help' => 'stack size',
        ],
        'limit' => [
            'alias' => 'l',
            'help' => 'limit size',
        ],
    ];

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $wc = new WooCommerce();
        $CliArgs = new CliArgs(static::$cliargs);
        static::$args = [
            'method' => $CliArgs->getArg('m'),
            'option' => $CliArgs->getArg('o'),
            'stack' => $CliArgs->getArg('s'),
            'limit' => $CliArgs->getArg('l'),
        ];
        switch (static::$args['method']) {
            case 'category':
                Exporter::runCategoriesScraper($wc);
                break;

            case 'product':
                if (!empty(static::$args['option'])) {
                    View::json($wc->getProductByID(static::$args['option']));
                } else {
                    Exporter::runProductsScraper($wc);
                }

                break;

            case 'product/attributes':
            case 'products/attributes':
                    View::json($wc->getProductAttributes());
                break;

            case 'products/variations':
                if (!empty(static::$args['option'])) {
                    View::json($wc->getProductVariations(static::$args['option']));
                }

                break;

            default:
                break;
        }
    }
}
