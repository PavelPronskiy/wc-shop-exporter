<?php

/**
 * FOR CLI ONLY
 */

define('RUN_METHOD', 'cli');
define("PATH", __DIR__);
define("APP_PATH", PATH . '/app/');

\libxml_use_internal_errors(true);

define('SHORTINIT', true);

require_once PATH . '/vendor/autoload.php';

new app\Config();
new app\Command();
