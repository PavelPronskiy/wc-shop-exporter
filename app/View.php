<?php

namespace app;

/**
 * visualization of the data that model contains.
 */
class View
{
    /**
     * { function_description }
     *
     * @param      array  $array  The array
     */
    public static function json($array)
    {
        echo json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * { function_description }
     *
     * @param      <type>  $string  The string
     */
    public static function cli($string)
    {
        echo $string . PHP_EOL;
    }
}
