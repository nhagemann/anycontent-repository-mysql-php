<?php

namespace AnyContent\Repository\Modules\Core\ResponseCache;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AnyContent\Repository\Modules\Core\Application\Application;

class ResponseCache
{

    public static function before(Request $request, Application $app)
    {

        if ($app['config']->getMinutesCachingFileListings() == 0)
        {
            if (self::isFileListRequest($request))
            {
                return;
            }
        }

        $token = self::getCacheToken($request, $app);

        if ($app['cache']->contains($token) AND $app['config']->getMinutesCachingData() != 0)
        {
            $response = new Response($app['cache']->fetch($token));
            $response->headers->set('Content-Type', 'application/json');

            $request->setMethod('CACHE'); // This informs the JSON prettifier to not prettify the cached output (again)

            return $response;
        }

    }


    public static function after(Request $request, Response $response, Application $app)
    {
        switch ($request->getMethod())
        {
            case 'GET':
                return self::afterRead($request, $response, $app);
                break;
            case 'POST';
                return self::afterWrite($request, $response, $app);
                break;
            case'DELETE':
                return self::afterWrite($request, $response, $app);
                break;
        }
    }


    public static function afterRead(Request $request, Response $response, Application $app)
    {
        if ($response->isOk())
        {

            $token = self::getCacheToken($request, $app);

            if (!$app['cache']->contains($token))
            {
                if (self::isFileListRequest($request))
                {
                    $minutes = $app['config']->getMinutesCachingFileListings();
                }
                else
                {
                    $minutes = $app['config']->getMinutesCachingData();
                }
                $app['cache']->save($token, $response->getContent(), $minutes * 60);
            }
        }
    }


    public static function afterWrite(Request $request, Response $response, Application $app)
    {
        $heartbeatToken = 'acr_heartbeat';
        $pathTokens     = explode('/', trim($request->getPathInfo(), '/'));

        if (isset($pathTokens[1]))
        {
            $heartbeatToken .= '_' . $pathTokens[1];
        }

        if (isset($pathTokens[3]) && $pathTokens[2] == 'content')
        {
            $heartbeatToken .= '_' . $pathTokens[3];
        }

        $app['cache']->delete($heartbeatToken);
    }


    protected static function getCacheToken(Request $request, Application $app)
    {
        $pulse = self::getHeartbeat($app, $request);

        $token = $pulse . $request->getQueryString();

        $token .= $request->get('_route');

        $token .= serialize($request->get('_route_params'));

        if ($app['config']->getMinutesCachingCMDL() == 0 AND !self::isFileListRequest($request))
        {
            $token .= $app['config']->getCMDLConfigHash();
        }

        return 'acr_response_' . md5($token);
    }


    protected static function getHeartbeat(Application $app, Request $request)
    {
        $heartbeatToken = 'acr_heartbeat';
        $pathTokens     = explode('/', trim($request->getPathInfo(), '/'));

        if (isset($pathTokens[1]))
        {
            $heartbeatToken .= '_' . $pathTokens[1];
        }

        if (isset($pathTokens[3]) && $pathTokens[2] == 'content')
        {
            $heartbeatToken .= '_' . $pathTokens[3];
        }

        if ($app['cache']->contains($heartbeatToken))
        {

            $pulse = $app['cache']->fetch($heartbeatToken);

        }
        else
        {
            $pulse = md5(microtime());
            $app['cache']->save($heartbeatToken, $pulse);
        }

        return $pulse;
    }


    protected function isFileListRequest(Request $request)
    {
        if (in_array($request->get('_route'), array( 'GET_1_repositoryName_files', 'GET_1_repositoryName_files_', 'GET_1_repositoryName_files_path' )))
        {
            return true;
        }

        return false;
    }

}