<?php

namespace Quark\Database\Query\Builder;
use Quark\Database\PDO;
use Quark\DB;

/**
 * Database query builder for UPDATE statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Update extends Where
{
    /**
     * UPDATE ...
     *
     * @var string|null
     */
    protected $_table;

    /**
     * SET ...
     *
     * @var array
     */
    protected $_set = array();

    /**
     * Set the table for a update.
     *
     * @param   mixed  $table  table name or array($table, $alias) or object
     */
    public function __construct($table = null)
    {
        if ($table)
        {
            // Set the inital table name
            $this->_table = $table;
        }

        // Start the query with no SQL
        return parent::__construct(DB::UPDATE, '');
    }

    /**
     * Sets the table to update.
     *
     * @param   mixed  $table  table name or array($table, $alias) or object
     * @return  $this
     */
    public function table($table)
    {
        $this->_table = $table;

        return $this;
    }

    /**
     * Set the values to update with an associative array.
     *
     * @param   array   $pairs  associative (column => value) list
     * @return  $this
     */
    public function set(array $pairs)
    {
        foreach ($pairs as $column => $value)
        {
            $this->_set[] = array($column, $value);
        }

        return $this;
    }

    /**
     * Set the value of a single column.
     *
     * @param   mixed  $column  table name or array($table, $alias) or object
     * @param   mixed  $value   column value
     * @return  $this
     */
    public function value($column, $value)
    {
        $this->_set[] = array($column, $value);

        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @param   mixed  $db  Database instance or name of instance
     * @return  string
     */
    public function compile($db = null)
    {
        if ( ! is_object($db))
        {
            // Get the database instance
            $db = PDO::instance($db);
        }

        // Start an update query
        $query = 'UPDATE '.$db->quote_table($this->_table);

        // Add the columns to update
        $query .= ' SET '.$this->_compile_set($db, $this->_set);

        if ( ! empty($this->_where))
        {
            // Add selection conditions
            $query .= ' WHERE '.$this->_compile_conditions($db, $this->_where);
        }

        if ( ! empty($this->_order_by))
        {
            // Add sorting
            $query .= ' '.$this->_compile_order_by($db, $this->_order_by);
        }

        if ($this->_limit !== null)
        {
            // Add limiting
            $query .= ' LIMIT '.$this->_limit;
        }

        $this->_sql = $query;

        return parent::compile($db);
    }

    public function reset()
    {
        $this->_table = null;

        $this->_set   =
        $this->_where = array();

        $this->_limit = null;

        $this->_parameters = array();

        $this->_sql = null;

        return $this;
    }
}
