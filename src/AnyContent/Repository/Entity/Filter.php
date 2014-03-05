<?php

namespace AnyContent\Repository\Entity;

use AnyContent\Repository\RepositoryException;

class Filter
{

    protected $conditionsArray = null;

    protected $block = 0;


    public function addCondition($property, $operator, $comparison)
    {
        $options = array( '=', '>', '<', '<=', '>=', '<>', '><','={}', '>{}', '<{}', '<={}', '>={}', '<>{}', '><{}' );

        if (!in_array($operator, $options))
        {
            throw new RepositoryException ('Invalid filter condition operator.', RepositoryException::REPOSITORY_BAD_PARAMS);
        }

        if (!$this->conditionsArray)
        {
            $this->conditionsArray                 = array();
            $this->conditionsArray[++$this->block] = array();
        }
        $this->conditionsArray[$this->block][] = array( $property, $operator, $comparison );

    }


    public function nextConditionsBlock()
    {
        if (!$this->conditionsArray)
        {
            $this->conditionsArray                 = array();
            $this->conditionsArray[++$this->block] = array();
        }
        else
        {
            $this->conditionsArray[++$this->block] = array();
        }
    }


    public function setConditionsArray($conditionsArray)
    {
        $this->conditionsArray = $conditionsArray;
    }


    public function getConditionsArray()
    {
        if (!$this->conditionsArray)
        {
            return array();
        }

        return $this->conditionsArray;
    }


    public function getMySQLExpression()
    {
        $blocks = array();
        $params = array();
        foreach ($this->getConditionsArray() AS $block)
        {
            $expressions = array();
            foreach ($block as $condition)
            {
                $expression = null;

                switch ($condition[1])
                {
                    case '=':
                        $expression = 'property_' . $condition[0] . ' = ?';
                        $params[]   = $condition[2];
                        break;
                    case '>':
                        $expression = 'property_' . $condition[0] . ' > ?';
                        $params[]   = $condition[2];
                        break;
                    case '<':
                        $expression = 'property_' . $condition[0] . ' < ?';
                        $params[]   = $condition[2];
                        break;
                    case '>=':
                        $expression = 'property_' . $condition[0] . ' >= ?';
                        $params[]   = $condition[2];
                        break;
                    case '<=':
                        $expression = 'property_' . $condition[0] . ' <= ?';
                        $params[]   = $condition[2];
                        break;
                    case '<>':
                        $expression = 'property_' . $condition[0] . ' <> ?';
                        $params[]   = $condition[2];
                        break;
                    case '><':
                        $expression   = 'property_' . $condition[0] . ' LIKE ?';
                        $condition[2] = '%' . $condition[2] . '%';
                        $params[]     = $condition[2];
                        break;
                    case '={}':
                        $expression = 'property_' . $condition[0] . ' = property_' . $condition[2];
                        break;
                    case '>{}':
                        $expression = 'property_' . $condition[0] . ' > property_' . $condition[2];
                        break;
                    case '<{}':
                        $expression = 'property_' . $condition[0] . ' < property_' . $condition[2];
                        break;
                    case '>={}':
                        $expression = 'property_' . $condition[0] . ' >= property_' . $condition[2];
                        break;
                    case '<={}':
                        $expression = 'property_' . $condition[0] . ' <= property_' . $condition[2];
                        break;
                    case '<>{}':
                        $expression = 'property_' . $condition[0] . ' <> property_' . $condition[2];
                        break;
                    case '><{}':
                        $expression = 'property_' . $condition[0] . ' LIKE property_' . $condition[2];

                        break;
                }

                if ($expression)
                {

                    $expressions[] = $expression;
                }

            }
            if (count($expressions) > 0)
            {
                $blocks[] = '(' . join(' OR ', $expressions) . ')';
            }
        }

        if (count($blocks) > 0)
        {
            return array( 'sql' => join(' AND ', $blocks), 'params' => $params );
        }

        return false;
    }
}

