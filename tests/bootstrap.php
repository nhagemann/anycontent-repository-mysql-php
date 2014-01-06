<?php

if (!defined('APPLICATION_PATH'))
{
    define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));
}

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('AnyContent\tests', __DIR__);