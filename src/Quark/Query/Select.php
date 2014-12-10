<?php

namespace Quark\Query;

use Quark\Exception\QuarkException;
use Quark\Statement\Join;
use Quark\Statement\Where;

/**
 * Database query builder for SELECT statements. See [Query Builder](/database/query/builder) for usage and examples.
 */
class Select extends Where implements QueryInterface
{
    /**
     * SELECT ...
     *
     * @var array
     */
    protected $select;

    /**
     * DISTINCT
     *
     * @var bool
     */
    protected $distinct;

    /**
     * FROM ...
     *
     * @var array
     */
    protected $from;

    /**
     * JOIN ...
     *
     * @var array
     */
    protected $join;

    /**
     * GROUP BY ...
     *
     * @var array
     */
    protected $groupBy;

    /**
     * HAVING ...
     *
     * @var array
     */
    protected $having;

    /**
     * OFFSET ...
     *
     * @var null|integer
     */
    protected $offset;

    /**
     * UNION ...
     *
     * @var array
     */
    protected $union;

    /**
     * The last JOIN statement created
     *
     * @var Join
     */
    protected $lastJoin;

    /**
     * Sets the initial columns to select from.
     *
     * @param   array  $columns  column list
     */
    public function __construct(array $columns = null)
    {
        $this->select   = array();
        $this->distinct = false;
        $this->from     = array();
        $this->join     = array();
        $this->groupBy  = array();
        $this->having   = array();
        $this->offset   = null;
        $this->union    = array();
        $this->lastJoin = null;

        $this->where    = array();
        $this->orderBy  = array();
        $this->limit    = null;

        if (!empty($columns)) {
            $this->select = $columns;
        }

        parent::__construct('');
    }

    /**
     * Enables or disables selecting only unique columns using "SELECT DISTINCT"
     *
     * @param   boolean  $value  enable or disable distinct columns
     * @return  $this
     */
    public function distinct($value)
    {
        $this->distinct = (bool) $value;

        return $this;
    }

    /**
     * Choose the columns to select from.
     *
     * @param   mixed  $columns  column name or array($column, $alias) or object
     * @return  $this
     */
    public function select($columns = null)
    {
        $columns = func_get_args();

        $this->select = array_merge($this->select, $columns);

        return $this;
    }

    /**
     * Choose the columns to select from, using an array.
     *
     * @param   array  $columns  list of column names or aliases
     * @return  $this
     */
    public function selectArray(array $columns)
    {
        $this->select = array_merge($this->select, $columns);

        return $this;
    }

    /**
     * Choose the tables to select "FROM ..."
     *
     * @param   mixed  $tables  table name or array($table, $alias) or object
     * @return  $this
     */
    public function from($tables)
    {
        $tables = func_get_args();

        $this->from = array_merge($this->from, $tables);

        return $this;
    }

    /**
     * Adds addition tables to "JOIN ...".
     *
     * @param   mixed   $table  column name or array($column, $alias) or object
     * @param   string  $type   join type (LEFT, RIGHT, INNER, etc)
     * @return  $this
     */
    public function join($table, $type = null)
    {
        $this->join[] = $this->lastJoin = new Join($table, $type);

        return $this;
    }

    /**
     * Adds "ON ..." conditions for the last created JOIN statement.
     *
     * @param   mixed   $c1  column name or array($column, $alias) or object
     * @param   string  $op  logic operator
     * @param   mixed   $c2  column name or array($column, $alias) or object
     * @return  $this
     */
    public function on($c1, $op, $c2)
    {
        $this->lastJoin->on($c1, $op, $c2);

        return $this;
    }

    /**
     * Adds "USING ..." conditions for the last created JOIN statement.
     *
     * @param   string  $columns  column name
     * @return  $this
     */
    public function using($columns)
    {
        $columns = func_get_args();

        call_user_func_array(array($this->lastJoin, 'using'), $columns);

        return $this;
    }

    /**
     * Creates a "GROUP BY ..." filter.
     *
     * @param   mixed  $columns  column name or array($column, $alias) or object
     * @return  $this
     */
    public function groupBy($columns)
    {
        $columns = func_get_args();

        $this->groupBy = array_merge($this->groupBy, $columns);

        return $this;
    }

    /**
     * Alias of and_having()
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function having($column, $op, $value = null)
    {
        return $this->andHaving($column, $op, $value);
    }

    /**
     * Creates a new "AND HAVING" condition for the query.
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function andHaving($column, $op, $value = null)
    {
        $this->having[] = array('AND' => array($column, $op, $value));

        return $this;
    }

    /**
     * Creates a new "OR HAVING" condition for the query.
     *
     * @param   mixed   $column  column name or array($column, $alias) or object
     * @param   string  $op      logic operator
     * @param   mixed   $value   column value
     * @return  $this
     */
    public function orHaving($column, $op, $value = null)
    {
        $this->having[] = array('OR' => array($column, $op, $value));

        return $this;
    }

    /**
     * Alias of and_having_open()
     *
     * @return  $this
     */
    public function havingOpen()
    {
        return $this->andHavingOpen();
    }

    /**
     * Opens a new "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function andHavingOpen()
    {
        $this->having[] = array('AND' => '(');

        return $this;
    }

    /**
     * Opens a new "OR HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function orHavingOpen()
    {
        $this->having[] = array('OR' => '(');

        return $this;
    }

    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function havingClose()
    {
        return $this->andHavingClose();
    }

    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function andHavingClose()
    {
        $this->having[] = array('AND' => ')');

        return $this;
    }

    /**
     * Closes an open "OR HAVING (...)" grouping.
     *
     * @return  $this
     */
    public function orHavingClose()
    {
        $this->having[] = array('OR' => ')');

        return $this;
    }

    /**
     * Adds an other UNION clause.
     *
     * @param   mixed    $select  if string, it must be the name of a table. Else must be an instance of Database_Query_Builder_Select
     * @param   boolean  $all     decides if it's an UNION or UNION ALL clause
     * @throws  \Quark\Exception\QuarkException
     * @return  $this
     */
    public function union($select, $all = true)
    {
        if (is_string($select)) {
            $qb     = new Select();
            $select = $qb->from($select);
        }

        if (!$select instanceof Select) {
            throw new QuarkException('first parameter must be a string or an instance of \Quark\Database\Query\Builder\Select');
        }

        $this->union[] = array('select' => $select, 'all' => $all);

        return $this;
    }

    /**
     * Start returning results after "OFFSET ..."
     *
     * @param   integer   $number  starting result number or null to reset
     * @return  $this
     */
    public function offset($number)
    {
        $this->offset = $number;

        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @return  string
     */
    public function compile()
    {
        $quote_column = array($this->quoter, 'quoteColumn');

        $quote_table = array($this->quoter, 'quoteTable');

        $query = 'SELECT ';

        if (true === $this->distinct) {
            $query .= 'DISTINCT ';
        }

        if (empty($this->select)) {
            $query .= '*';
        } else {
            $query .= implode(', ', array_unique(array_map($quote_column, $this->select)));
        }

        if (!empty($this->from)) {
            $query .= ' FROM '.implode(', ', array_unique(array_map($quote_table, $this->from)));
        }

        if (!empty($this->join)) {
            $query .= ' '.$this->compileJoin($this->join);
        }

        if (!empty($this->where)) {
            $query .= ' WHERE '.$this->compileConditions($this->where);
        }

        if (!empty($this->groupBy)) {
            $query .= ' '.$this->compileGroupBy($this->groupBy);
        }

        if (!empty($this->having)) {
            $query .= ' HAVING '.$this->compileConditions($this->having);
        }

        if (!empty($this->orderBy)) {
            $query .= ' '.$this->compileOrderBy($this->orderBy);
        }

        if ($this->limit !== null) {
            $query .= ' LIMIT '.$this->limit;
        }

        if ($this->offset !== null) {
            $query .= ' OFFSET '.$this->offset;
        }

        if (!empty($this->union)) {
            $query = '('.$query.')';

            foreach ($this->union as $u) {
                $query .= ' UNION ';

                if ($u['all'] === true) {
                    $query .= 'ALL ';
                }

                $query .= '('.$u['select']->compile().')';
            }
        }

        $this->sql = $query;

        return parent::compile();
    }

    /**
     * Reset query
     *
     * @return $this
     */
    public function reset()
    {
        $this->select   = array();
        $this->distinct = false;
        $this->from     = array();
        $this->join     = array();
        $this->groupBy  = array();
        $this->having   = array();
        $this->offset   = null;
        $this->union    = array();
        $this->lastJoin = null;

        $this->where    = array();
        $this->orderBy  = array();
        $this->limit    = null;

        $this->sql      = null;

        return $this;
    }
}
