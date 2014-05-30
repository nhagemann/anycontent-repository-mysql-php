<?php

namespace AnyContent\Repository\Modules\Core\ResponseCache;

use AnyContent\Repository\Modules\Core\Application\Application;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        $app->before('AnyContent\Repository\Modules\Core\ResponseCache\ResponseCache::before');
        $app->after('AnyContent\Repository\Modules\Core\ResponseCache\ResponseCache::after');
    }

}