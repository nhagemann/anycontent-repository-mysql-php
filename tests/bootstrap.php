<?php

if (!defined('APPLICATION_PATH'))
{
    define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));
}

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('AnyContent\tests', __DIR__);

if (!function_exists('apc_exists'))
{
    function apc_exists($keys)
    {
        $result = null;
        apc_fetch($keys, $result);

        return $result;
    }
}