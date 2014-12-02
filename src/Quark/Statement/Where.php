<?php

namespace Quark\Statement;

use Quark\Worker\Builder;

/**
 * Database query builder for WHERE statements. See [Query Builder](/database/query/builder) for usage and examples.
 */
abstract class Where extends Builder
{
    /**
     * WHERE ...
     *
     * @var array
     */
    protected $where;

    /**
     * ORDER BY ...
     *
     * @var array
     */
    protected $orderBy;

    /**
     * LIMIT ...
     *
     * @var null|integer
     */
    protected $limit;

    /**
     * Alias of and_where()
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function where($column, $op, $value)
    {
        return $this->andWhere($column, $op, $value);
    }

    /**
     * Creates a new "AND WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function andWhere($column, $op, $value)
    {
        $this->where[] = array('AND' => array($column, $op, $value));

        return $this;
    }

    /**
     * Creates a new "OR WHERE" condition for the query.
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function orWhere($column, $op, $value)
    {
        $this->where[] = array('OR' => array($column, $op, $value));

        return $this;
    }

    /**
     * Alias of and_where_open()
     *
     * @return  $this
     */
    public function whereOpen()
    {
        return $this->andWhereOpen();
    }

    /**
     * Opens a new "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function andWhereOpen()
    {
        $this->where[] = array('AND' => '(');

        return $this;
    }

    /**
     * Opens a new "OR WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function orWhereOpen()
    {
        $this->where[] = array('OR' => '(');

        return $this;
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function whereClose()
    {
        return $this->andWhereClose();
    }

    /**
     * Closes an open "WHERE (...)" grouping or removes the grouping when it is
     * empty.
     *
     * @return  $this
     */
    public function whereCloseEmpty()
    {
        $group = end($this->where);

        if ($group && reset($group) === '(') {
            array_pop($this->where);

            return $this;
        }

        return $this->whereClose();
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function andWhereClose()
    {
        $this->where[] = array('AND' => ')');

        return $this;
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function orWhereClose()
    {
        $this->where[] = array('OR' => ')');

        return $this;
    }

    /**
     * Applies sorting with "ORDER BY ..."
     *
     * @param   mixed   $column     column name or array($column, $alias) or object
     * @param   string  $direction  direction of sorting
     * @return  $this
     */
    public function orderBy($column, $direction = null)
    {
        $this->orderBy[] = array($column, $direction);

        return $this;
    }

    /**
     * Return up to "LIMIT ..." results
     *
     * @param   integer  $number  maximum results to return or null to reset
     * @return  $this
     */
    public function limit($number)
    {
        $this->limit = $number;

        return $this;
    }
}
