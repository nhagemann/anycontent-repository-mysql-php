<?php

namespace AnyContent\Repository\Modules\Core\Repositories;

use AnyContent\Repository\Modules\Core\Application\Application;


class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        $app['repos'] = $app->share(function ($app)
        {
            return new RepositoryManager($app);
        });

        // get repository status (additional query parameters: timeshift, language)
        $app->get('/1/{repositoryName}/info', 'AnyContent\Repository\Controller\RepositoryController::index');

        //->before($before1)
        //    ->before($before2)->before($before3)->after($afterRead);

        $app->get('/1/{repositoryName}/info/{workspace}', 'AnyContent\Repository\Controller\RepositoryController::index');
        //
        //    ->before($before1)->before($before2)->before($before3)->after($afterRead);
    }

}