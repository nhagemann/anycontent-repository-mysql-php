<?php

namespace AnyContent\Repository\Modules\Core\ContentRecords;

use AnyContent\Repository\Modules\Core\Application\Application;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

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

        // simplification routes, solely for human interaction with the api
        $app->get('/1/{repositoryName}/content/{contentTypeName}', 'AnyContent\Repository\Controller\ContentController::getContentShortCut');
    }

}