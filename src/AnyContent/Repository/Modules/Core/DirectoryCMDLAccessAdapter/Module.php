<?php

namespace AnyContent\Repository\Modules\Core\DirectoryCMDLAccessAdapter;

use AnyContent\Repository\Modules\Core\Application\Application;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        $app->registerCMDLAccessAdapter('directory', 'AnyContent\Repository\Modules\Core\DirectoryCMDLAccessAdapter\DirectoryCMDLAccessAdapter');

    }

}