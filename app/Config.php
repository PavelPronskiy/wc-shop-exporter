<?php

namespace app;

/**
 * Configuration variables and functions
 */
class Config
{
    /**
     * reg
     *
     * @var [type]
     */
    public static $reg;
    const CONFIG_GLOBAL = PATH . '/config.json';


    public function __construct()
    {
        static::$reg = (object) static::getGlobalConfig();
    }


    /**
     * Gets the global configuration.
     *
     * @return     array|object  The global configuration.
     */
    public static function getGlobalConfig(): object
    {
        $config_json = [];
        if (file_exists(static::CONFIG_GLOBAL)) {
            $config_json = json_decode(file_get_contents(static::CONFIG_GLOBAL));
            if (json_last_error() > 0) {
                die(json_last_error_msg() . ' ' . static::CONFIG_GLOBAL);
            }
        } else {
            die('Global config: ' . static::CONFIG_GLOBAL . ' not found');
        }

        return $config_json;
    }


    /**
     * { function_description }
     *
     * @param      float|int  $microtime  The microtime
     *
     * @return     string     ( description_of_the_return_value )
     */
    public static function microtimeAgo(float $microtime): string
    {
        return round((microtime(true) - $microtime) * 1000, 2) . 's';
    }
}
