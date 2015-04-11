<?php

namespace AnyContent\Repository\Modules\Core\ContentRecords;

class FilterFactory
{

    /**
     * @param $query
     *
     * @return Filter
     */
    public static function createFromQuery($query)
    {

        $query = self::escape($query);

        $filter = new Filter();

        $blocks = explode('+', $query);

        foreach ($blocks as $block)
        {
            $filter->nextConditionsBlock();

            $conditions = explode(',', $block);
            foreach ($conditions as $conditionString)
            {
                $condition = self::parseCondition($conditionString);
                if (is_array($condition))
                {
                    $filter->addCondition($condition[0], $condition[1], $condition[2]);
                }
            }
        }

        return $filter;
    }


    protected static function escape($s)
    {
        $s = str_replace('\\+', '&#43;', $s);
        $s = str_replace('\\,', '&#44;', $s);
        $s = str_replace('\\=', '&#61;', $s);

        return $s;
    }


    protected static function decode($s)
    {
        $s = str_replace('&#43;', '+', $s);
        $s = str_replace('&#44;', ',', $s);
        $s = str_replace('&#61;', '=', $s);

        // remove surrounding quotes
        if (substr($s, 0, 1) == '"')
        {

            $s = trim($s, '"');
        }
        else
        {

            $s = trim($s, "'");
        }

        return $s;
    }


    /**
     * http://stackoverflow.com/questions/4955433/php-multiple-delimiters-in-explode
     *
     * @param $s
     *
     * @return bool
     */
    protected static function parseCondition($s)
    {

        $match = preg_match("/([^>=|<=|<>|><|>|<|=)]*)(>=|<=|<>|><|>|<|=)(.*)/", $s, $matches);

        if ($match)
        {
            $condition   = array();
            $condition[] = self::decode(trim($matches[1]));
            $condition[] = trim($matches[2]);
            $condition[] = self::decode(trim($matches[3]));
            return $condition;
        }

        return false;
    }


    /**
     * @param $array
     *
     * @return Filter
     *
     * @deprecated won't be part of the CMDL 1.0 specification
     */
    public static function createFromArray($array)
    {
        $filter = new Filter();

        foreach ($array AS $block)
        {
            $filter->nextConditionsBlock();
            foreach ($block as $condition)
            {
                $filter->addCondition($condition[0], $condition[1], $condition[2]);
            }
        }

        return $filter;
    }
}