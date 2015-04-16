<?php

namespace AnyContent\Repository\Modules\Core\ContentRecords;

use AnyContent\Repository\Modules\Core\Application\Application;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        // list content
        $app->get('/1/{repositoryName}/content', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::index');

        // get record (additional query parameters: timeshift, language)
        $app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::getOne');
        $app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::getOne');
        $app->get('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}/{clippingName}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::getOne');

        // get records (additional query parameters: timeshift, language, order, properties, limit, page, subset, filter)
        $app->get('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::getMany');
        $app->get('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::getMany');
        $app->get('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}/{clippingName}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::getMany');

        // delete record (additional query parameter: language)
        $app->delete('/1/{repositoryName}/content/{contentTypeName}/record/{id}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::deleteOne');
        $app->delete('/1/{repositoryName}/content/{contentTypeName}/record/{id}/{workspace}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::deleteOne');

        // delete records (additional query parameter: language, reset)
        $app->delete('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::truncate');
        $app->delete('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::truncate');

        // insert/update record (additional query parameters: record, language)
        $app->post('/1/{repositoryName}/content/{contentTypeName}/records', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::post');
        $app->post('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::post');
        $app->post('/1/{repositoryName}/content/{contentTypeName}/records/{workspace}/{clippingName}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::post');

        // sort records (additional query parameters: list, language)
        $app->post('/1/{repositoryName}/content/{contentTypeName}/sort-records', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::sort');
        $app->post('/1/{repositoryName}/content/{contentTypeName}/sort-records/{workspace}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::sort');

        // simplification routes, solely for human interaction with the api
        $app->get('/1/{repositoryName}/content/{contentTypeName}', 'AnyContent\Repository\Modules\Core\ContentRecords\ContentController::getContentShortCut');
    }

}