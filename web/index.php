<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AnyContent\Service\RepositoryManager;
use AnyContent\Service\Config;
use AnyContent\Service\Database;

$app = new Silex\Application();
$app['debug'] = true;


$app->get('/1/{repositoryName}', 'AnyContent\Controller\RepositoryController::index');
$app->get('/1/{repositoryName}/', 'AnyContent\Controller\RepositoryController::index');

$app->get('/1/{repositoryName}/cmdl/{contentTypeName}', 'AnyContent\Controller\RepositoryController::cmdl');
$app->get('/1/{repositoryName}/cmdl/{contentTypeName}/', 'AnyContent\Controller\RepositoryController::cmdl');

$app->get('/admin/refresh/{repositoryName}/{contentTypeName}', 'AnyContent\Controller\AdminController::refresh');


$app['config'] = $app->share(function ($app) {
    return new Config($app,'../');
});

$app['db'] = $app->share(function ($app) {
    return new Database($app);
});

$app['repos'] = $app->share(function ($app) {
    return new RepositoryManager($app);
});

// test, whether we can handle access via middleware, we can
$app->before(function (Symfony\Component\HttpFoundation\Request $request) {
    //var_dump ($request->get('_route'));
    //var_dump ($request->get('repositoryName'));
});

$app->run();