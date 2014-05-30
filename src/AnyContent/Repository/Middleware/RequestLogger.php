<?php

namespace AnyContent\Repository\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AnyContent\Repository\Modules\Core\Application\Application;

/**
 * Class RequestLogger
 *
 * @package AnyContent\Repository\Middleware
 */
class RequestLogger
{

    public static function execute(Request $request, Application $app)
    {
        if (isset($app['monolog']))
        {
            $app['monolog']->addDebug('');
            $app['monolog']->addDebug('');
            $app['monolog']->addDebug('');
            $app['monolog']->addDebug('===========================================================');
            $app['monolog']->addDebug(str_pad('URL ', 16, '.') . ': ' . $request->getUri());
            $app['monolog']->addDebug(str_pad('QUERY ', 16, '.') . ': ' . urldecode($request->getQueryString()));

            foreach ($request->query as $k => $v)
            {
                $s = str_pad('G ' . $k . ' ', 16, '.') . ': ' . preg_replace('/\s+/', ' ', print_r($v, true));
                $app['monolog']->addDebug($s);
            }
            foreach ($request->request as $k => $v)
            {
                $s = str_pad('P ' . $k . ' ', 16, '.') . ': ' . preg_replace('/\s+/', ' ', print_r($v, true));
                $app['monolog']->addDebug($s);
            }

            $app['monolog']->addDebug('^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^');
            $app['monolog']->addDebug('===========================================================');
            $app['monolog']->addDebug('');
            $app['monolog']->addDebug('');
            $app['monolog']->addDebug('');
        }
    }
}