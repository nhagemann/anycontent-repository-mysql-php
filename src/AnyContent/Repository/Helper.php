<?php

namespace AnyContent\Repository;

class Helper
{

    /**
     * @link http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
     *
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }


    /**
     * @link http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
     *
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }


    /**
     * @link http://stackoverflow.com/questions/6054033/pretty-printing-json-with-php
     *
     * @param $json
     *
     * @return string
     */
    public static function prettyPrintJSON($json)
    {
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
            else {
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

        return $result;
    }

}