<?php

namespace AnyContent\Repository\Modules\Extend\PrettyJSON;

use AnyContent\Repository\Modules\Core\Application\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Module extends \AnyContent\Repository\Modules\Core\Application\Module
{

    public function init(Application $app, $options = array())
    {
        parent::init($app, $options);

        $app->after(function (Request $request, Response $response, Application $app)
        {
            if ($response->headers->has('content-type'))
            {
                if ($response->headers->get('content-type') == 'application/json')
                {

                    // http://stackoverflow.com/questions/6054033/pretty-printing-json-with-php

                    $json = $response->getContent();

                    $result          = '';
                    $level           = 0;
                    $prev_char       = '';
                    $in_quotes       = false;
                    $ends_line_level = NULL;
                    $json_length     = strlen($json);

                    for ($i = 0; $i < $json_length; $i++)
                    {
                        $char           = $json[$i];
                        $new_line_level = NULL;
                        $post           = "";
                        if ($ends_line_level !== NULL)
                        {
                            $new_line_level  = $ends_line_level;
                            $ends_line_level = NULL;
                        }
                        if ($char === '"' && $prev_char != '\\')
                        {
                            $in_quotes = !$in_quotes;
                        }
                        else
                        {
                            if (!$in_quotes)
                            {
                                switch ($char)
                                {
                                    case '}':
                                    case ']':
                                        $level--;
                                        $ends_line_level = NULL;
                                        $new_line_level  = $level;
                                        break;

                                    case '{':
                                    case '[':
                                        $level++;
                                    case ',':
                                        $ends_line_level = $level;
                                        break;

                                    case ':':
                                        $post = " ";
                                        break;

                                    case " ":
                                    case "\t":
                                    case "\n":
                                    case "\r":
                                        $char            = "";
                                        $ends_line_level = $new_line_level;
                                        $new_line_level  = NULL;
                                        break;
                                }
                            }
                        }
                        if ($new_line_level !== NULL)
                        {
                            $result .= "\n" . str_repeat("  ", $new_line_level);
                        }
                        $result .= $char . $post;
                        $prev_char = $char;
                    }

                    $response->setContent($result);

                }
            }

        });
    }

}