<?php

namespace AnyContent\Repository\Modules\Core\DatabaseCMDLAccessAdapter;

use AnyContent\Repository\Modules\Core\Application\Application;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        $app->registerCMDLAccessAdapter('database', 'AnyContent\Repository\Modules\Core\DatabaseCMDLAccessAdapter\DatabaseCMDLAccessAdapter');

    }

}