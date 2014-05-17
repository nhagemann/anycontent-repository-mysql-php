<?php

namespace AnyContent\Repository\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

class ResponseCache
{

    public static function before(Request $request, Application $app)
    {

        $token = self::getCacheToken($request, $app);

        if ($app['cache']->contains($token))
        {
            $response = new Response($app['cache']->fetch($token));
            $response->headers->set('Content-Type', 'application/json');

            $request->setMethod('CACHE');

            return $response;
        }

    }


    public static function afterRead(Request $request, Response $response, Application $app)
    {
        if ($response->isOk())
        {

            $token = self::getCacheToken($request, $app);

            if (!$app['cache']->contains($token))
            {
                $app['cache']->save($token, $response->getContent(), 600);
            }
        }
    }


    public static function afterWrite(Request $request, Response $response, Application $app)
    {
        $app['cache']->delete('acrs_heartbeat');
    }


    protected function getCacheToken(Request $request, Application $app)
    {
        $pulse = self::getHeartbeat($app);

        $token = $pulse . $request->getQueryString();

        $token .= $request->get('_route');

        $token .= serialize($request->get('_route_params'));

        if ($app['debug'])
        {
            $token .= $app['config']->getLastCMDLConfigChangeTimestamp();
        }

        return md5('acrs_response_' . $token);
    }


    protected function getHeartbeat(Application $app)
    {
        if ($app['cache']->contains('acrs_heartbeat'))
        {

            $pulse = $app['cache']->fetch('acrs_heartbeat');

        }
        else
        {
            $pulse = md5(microtime());
            $app['cache']->save('acrs_heartbeat', $pulse, 600);
        }

        return $pulse;
    }

}