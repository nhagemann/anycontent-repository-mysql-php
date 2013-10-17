<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\Service\ContentManager;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;

$app          = new Silex\Application();
$app['debug'] = true;

$before = 'AnyContent\Repository\Middleware\ExtractUserInfo::execute';

$app->get('/1/{repositoryName}', 'AnyContent\Repository\Controller\RepositoryController::index')->before($before);

$app->get('/1/{repositoryName}/cmdl/{contentTypeName}', 'AnyContent\Repository\Controller\RepositoryController::cmdl')->before($before);

$app->post('/1/{repositoryName}/content/{contentTypeName}', 'AnyContent\Repository\Controller\ContentController::post')->before($before);
$app->post('/1/{repositoryName}/content/{contentTypeName}/{clippingName}/{workspace}', 'AnyContent\Repository\Controller\ContentController::post')->before($before);
$app->post('/1/{repositoryName}/content/{contentTypeName}/{clippingName}/{workspace}/{language}', 'AnyContent\Repository\Controller\ContentController::post')->before($before);

$app->get('/admin/refresh/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::refresh')->before($before);
$app->get('/admin/delete/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::delete')->before($before);


$app['config'] = $app->share(function ($app)
{
    return new Config($app, '../');
});

$app['db'] = $app->share(function ($app)
{
    return new Database($app);
});

$app['repos'] = $app->share(function ($app)
{
    return new RepositoryManager($app);
});

/*
$app['cm'] = $app->share(function ($app)
{
    return new ContentManager($app);
});
*/

//$app->before('AnyContent\Repository\Middleware\ExtractUserInfo::execute');
// test, whether we can handle access via middleware, we can
/*
$app->before(function (Symfony\Component\HttpFoundation\Request $request)
{
    //var_dump ($request->get('_route'));
    //var_dump ($request->get('repositoryName'));
});   */

$app->run();