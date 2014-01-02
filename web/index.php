<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\Service\ContentManager;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;

$app          = new Silex\Application();
$app['debug'] = true;

// extracting apiuser (authentifcation) and userinfo (query parameter userinfo)
$before1 = 'AnyContent\Repository\Middleware\ExtractUserInfo::execute';

$before2 = 'AnyContent\Repository\Middleware\RequestLogger::execute';

// json formatter to make json human readable
$after = 'AnyContent\Repository\Middleware\PrettyPrint::execute';

// get repository status (additional query parameters: timeshift, language)
$app->get('/1/{repositoryName}', 'AnyContent\Repository\Controller\RepositoryController::index')->before($before1)->before($before2);
$app->get('/1/{repositoryName}/info', 'AnyContent\Repository\Controller\RepositoryController::index')->before($before1)->before($before2);
$app->get('/1/{repositoryName}/info/{workspace}', 'AnyContent\Repository\Controller\RepositoryController::index')->before($before1)->before($before2);

// get cmdl for a content type
$app->get('/1/{repositoryName}/cmdl/{contentTypeName}', 'AnyContent\Repository\Controller\RepositoryController::cmdl')->before($before1)->before($before2);


// get distinct record (additional query parameters: timeshift, language)
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}', 'AnyContent\Repository\Controller\ContentController::getOne')->before($before1)->before($before2);
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}', 'AnyContent\Repository\Controller\ContentController::getOne')->before($before1)->before($before2);
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::getOne')->before($before1)->before($before2);

// get records (additional query parameters: timeshift, language, order, properties, limit, page, subset, filter)
$app->get('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Controller\ContentController::getMany')->before($before1)->before($before2);
$app->get('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}', 'AnyContent\Repository\Controller\ContentController::getMany')->before($before1)->before($before2);
$app->get('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::getMany')->before($before1)->before($before2);

// delete record (additional query parameter: language)
$app->delete('/1/{repositoryName}/content/{contentTypeName}/record/{id}', 'AnyContent\Repository\Controller\ContentController::deleteOne')->before($before1)->before($before2);
$app->delete('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}', 'AnyContent\Repository\Controller\ContentController::deleteOne')->before($before1)->before($before2);

// insert/update record (additional query parameters: language)
$app->post('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Controller\ContentController::post')->before($before1)->before($before2);
$app->post('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::post')->before($before1)->before($before2);


// list files
$app->get('/1/{repositoryName}/files/{workspace}/list', 'AnyContent\Repository\Controller\FilesController::scan')->before($before1)->before($before2);
$app->get('/1/{repositoryName}/files/{workspace}/list/', 'AnyContent\Repository\Controller\FilesController::scan')->before($before1)->before($before2);
$app->get('/1/{repositoryName}/files/{workspace}/list/{path}', 'AnyContent\Repository\Controller\FilesController::scan')->before($before1)->before($before2)->assert('path', '.+');;

// admin routes
$app->get('/1/admin/refresh/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::refresh')->before($before1)->before($before2);
$app->get('/1/admin/delete/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::delete')->before($before1)->before($before2);


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


if ($app['debug'])
{
    $app->register(new Silex\Provider\MonologServiceProvider(), array(
        'monolog.logfile' => __DIR__.'/../log/debug.log',
    ));
}


$app->after($after);
$app->run();