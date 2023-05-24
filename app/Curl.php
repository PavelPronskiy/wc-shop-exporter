<?php
namespace app;

// use app\module\Tilda;
// use app\module\Wix;

class Curl
{
    /**
     * [curlErrorHandler description]
     * @param  [type] $http_code      [description]
     * @return [type] [description]
     */
    public static function curlErrorHandler($http_code)
    {
        switch ($http_code) {
            case 200:
                $bool = true;
                break;

            default:
                $bool = false;
                break;
        }

        return $bool;
    }

    /**
     * [get description]
     * @param  [type] $url            [description]
     * @return [type] [description]
     */
    public static function get($url)
    {
        $curl = \curl_init ();

        if (Config::$reg->privoxy->enabled) {
            curl_setopt($curl, CURLOPT_PROXY,
                Config::$reg->privoxy->host . ':' .
                Config::$reg->privoxy->port
            );
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, Config::$reg->headers->ua);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_ENCODING, "gzip");

        $response     = curl_exec($curl);
        $info         = curl_getinfo($curl);
        $content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        // $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (self::curlErrorHandler($http_code)) {
            return (object) [
                'body'         => $response,
                'status'       => $http_code,
                'content_type' => $content_type,
            ];
        } else {
            return self::curlErrorHandler(502);
        }
    }
}
