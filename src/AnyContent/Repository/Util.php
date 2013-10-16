<?php

namespace AnyContent\Repository;

class Util
{

    /**
     * @link http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
     *
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }


    /**
     * @link http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
     *
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

}