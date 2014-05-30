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

        // get info on repositories
        $app->get('/1/{repositoryName}/info', 'AnyContent\Repository\Controller\RepositoryController::index');
        $app->get('/1/{repositoryName}/info/{workspace}', 'AnyContent\Repository\Controller\RepositoryController::index');

        // get cmdl for a content type
        $app->get('/1/{repositoryName}/content/{contentTypeName}/cmdl', 'AnyContent\Repository\Controller\RepositoryController::cmdl');
        $app->get('/1/{repositoryName}/content/{contentTypeName}/cmdl/{locale}', 'AnyContent\Repository\Controller\RepositoryController::cmdl');

        // simplification routes, solely for human interaction with the api
        $app->get('/', 'AnyContent\Repository\Controller\RepositoryController::welcomeShortCut');
        $app->get('/1', 'AnyContent\Repository\Controller\RepositoryController::welcome');
        $app->get('/1/', 'AnyContent\Repository\Controller\RepositoryController::welcome');
        $app->get('/1/{repositoryName}', 'AnyContent\Repository\Controller\RepositoryController::getInfoShortCut');
    }

}