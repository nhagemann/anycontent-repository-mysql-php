<?php
if (!defined('APPLICATION_PATH'))
{
    define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));
}

require_once __DIR__ . '/../vendor/autoload.php';

use AnyContent\Repository\Service\ContentManager;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;

$app          = new \AnyContent\Repository\Modules\Core\Application\Application;
$app['debug'] = true;

// Detect environment (default: prod) by checking for the existence of $app_env
if (isset($app_env) && in_array($app_env, array( 'prod', 'dev', 'test', 'console' )))
{
    $app['env'] = $app_env;
}
else
{
    $app['env'] = 'prod';
}

$app->registerModule('AnyContent\Repository\Modules\Core\Repositories');
$app->registerModule('AnyContent\Repository\Modules\Core\ResponseCache');
$app->registerModule('AnyContent\Repository\Modules\Core\ExtractUserInfo');




// list content
$app->get('/1/{repositoryName}/content', 'AnyContent\Repository\Controller\ContentController::index');

// get record (additional query parameters: timeshift, language)
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}', 'AnyContent\Repository\Controller\ContentController::getOne');
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}', 'AnyContent\Repository\Controller\ContentController::getOne');
$app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::getOne');

// get records (additional query parameters: timeshift, language, order, properties, limit, page, subset, filter)
$app->get('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Controller\ContentController::getMany');
$app->get('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}', 'AnyContent\Repository\Controller\ContentController::getMany');
$app->get('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::getMany');

// delete record (additional query parameter: language)
$app->delete('/1/{repositoryName}/content/{contentTypeName}/record/{id}', 'AnyContent\Repository\Controller\ContentController::deleteOne');
$app->delete('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}', 'AnyContent\Repository\Controller\ContentController::deleteOne');

// insert/update record (additional query parameters: record, language)
$app->post('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Controller\ContentController::post');
$app->post('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}/{clippingName}', 'AnyContent\Repository\Controller\ContentController::post');

// sort records (additional query parameters: list, language)
$app->post('/1/{repositoryName}/content/{contentTypeName}/sort-records', 'AnyContent\Repository\Controller\ContentController::sort');
$app->post('/1/{repositoryName}/content/{contentTypeName}/sort-records/{workspace}', 'AnyContent\Repository\Controller\ContentController::sort');

// get cmdl for a content type
$app->get('/1/{repositoryName}/content/{contentTypeName}/cmdl', 'AnyContent\Repository\Controller\RepositoryController::cmdl');
$app->get('/1/{repositoryName}/content/{contentTypeName}/cmdl/{locale}', 'AnyContent\Repository\Controller\RepositoryController::cmdl');

// get records status for a content type (additional query parameter: language)
//$app->get('/1/{repositoryName}/content/{contentTypeName}/info', 'AnyContent\Repository\Controller\ContentController::info');
//$app->get('/1/{repositoryName}/content/{contentTypeName}/info/{workspace}', 'AnyContent\Repository\Controller\ContentController::info');

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

// get binary file
$app->get('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::binary')
    ->assert('path', '.+');

// list files
$app->get('/1/{repositoryName}/files', 'AnyContent\Repository\Controller\FilesController::scan');
$app->get('/1/{repositoryName}/files/', 'AnyContent\Repository\Controller\FilesController::scan');
$app->get('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::scan')
    ->assert('path', '.+');

// save file (post body contains binary)
$app->post('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::postFile')
    ->assert('path', '.+');

// create folder
$app->post('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::createFolder')
    ->assert('path', '.+');

// delete file
$app->delete('/1/{repositoryName}/file/{path}', 'AnyContent\Repository\Controller\FilesController::deleteFile')
    ->assert('path', '.+');

// delete files
$app->delete('/1/{repositoryName}/files/{path}', 'AnyContent\Repository\Controller\FilesController::deleteFiles')
    ->assert('path', '.+');
$app->delete('/1/{repositoryName}/files', 'AnyContent\Repository\Controller\FilesController::deleteFiles');
$app->delete('/1/{repositoryName}/files/', 'AnyContent\Repository\Controller\FilesController::deleteFiles');

// simplification routes, solely for human interaction with the api
$app->get('/', 'AnyContent\Repository\Controller\RepositoryController::welcomeShortCut');
$app->get('/1', 'AnyContent\Repository\Controller\RepositoryController::welcome');
$app->get('/1/', 'AnyContent\Repository\Controller\RepositoryController::welcome');
$app->get('/1/{repositoryName}', 'AnyContent\Repository\Controller\RepositoryController::getInfoShortCut');
$app->get('/1/{repositoryName}/config/{configTypeName}', 'AnyContent\Repository\Controller\ConfigController::getConfigShortCut');
$app->get('/1/{repositoryName}/content/{contentTypeName}', 'AnyContent\Repository\Controller\ContentController::getContentShortCut');

// admin routes
$app->get('/1/admin/refresh/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::refresh');
$app->get('/1/admin/delete/{repositoryName}/{contentTypeName}', 'AnyContent\Repository\Controller\AdminController::delete');

$app['config'] = $app->share(function ($app)
{
    return new Config($app, __DIR__ . '/../');
});

$app['db'] = $app->share(function ($app)
{
    return new Database($app);
});

if ($app['debug'])
{
    $app->register(new Silex\Provider\MonologServiceProvider(), array(
        'monolog.logfile' => __DIR__ . '/../log/debug.log', 'monolog.level' => \Monolog\Logger::INFO
    ));
}

if (!function_exists('apc_exists'))
{
    function apc_exists($keys)
    {
        $result = null;
        apc_fetch($keys, $result);

        return $result;
    }
}


// json formatter to make json human readable
//$afterJson = 'AnyContent\Repository\Middleware\PrettyPrint::execute';
//$app->after($afterJson, Silex\Application::EARLY_EVENT);

$app->registerStorageAdapter('directory', 'AnyContent\Repository\Modules\StorageAdapter\Directory\DirectoryStorageAdapter');
$app->registerStorageAdapter('s3', 'AnyContent\Repository\Modules\StorageAdapter\S3\S3StorageAdapter');
$app->registerStorageAdapter('s3pp', 'AnyContent\Repository\Modules\StorageAdapter\S3\S3PPStorageAdapter');

require_once(APPLICATION_PATH . '/config/modules.php');

$app->init();

if ($app['env'] == 'test')
{
    return $app;
}

$app->run();
