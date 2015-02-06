<?php

namespace AnyContent\Repository\Modules\Core\ConfigRecords;

use AnyContent\Repository\Modules\Core\Application\Application;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        // list configs
        $app->get('/1/{repositoryName}/config', 'AnyContent\Repository\Controller\ConfigController::index');

        // get cmdl for a config type
        $app->get('/1/{repositoryName}/config/{configTypeName}/cmdl', 'AnyContent\Repository\Controller\ConfigController::cmdl');
        $app->get('/1/{repositoryName}/config/{configTypeName}/cmdl/{locale}', 'AnyContent\Repository\Controller\ConfigController::cmdl');

        // get config (additional query parameters: timeshift, language)
        $app->get('/1/{repositoryName}/config/{configTypeName}/record', 'AnyContent\Repository\Controller\ConfigController::getConfig');
        $app->get('/1/{repositoryName}/config/{configTypeName}/record/{workspace}', 'AnyContent\Repository\Controller\ConfigController::getConfig');

        // insert/update config (additional query parameters: language)
        $app->post('/1/{repositoryName}/config/{configTypeName}/record', 'AnyContent\Repository\Controller\ConfigController::post');
        $app->post('/1/{repositoryName}/config/{configTypeName}/record/{workspace}', 'AnyContent\Repository\Controller\ConfigController::post');

        // simplification routes, solely for human interaction with the api
        $app->get('/1/{repositoryName}/config/{configTypeName}', 'AnyContent\Repository\Controller\ConfigController::getConfigShortCut');
    }

}