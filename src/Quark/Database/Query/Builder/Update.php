<?php

namespace Quark\Database\Query\Builder;
use Quark\Database\PDO;
use Quark\DB;

/**
 * Database query builder for UPDATE statements. See [Query Builder](/database/query/builder) for usage and examples.
 */
class Update extends Where
{
    /**
     * UPDATE ...
     *
     * @var string|null
     */
    protected $table;

    /**
     * SET ...
     *
     * @var array
     */
    protected $set;

    /**
     * Set the table for a update.
     *
     * @param   mixed  $table  table name or array($table, $alias) or object
     */
    public function __construct($table = null)
    {
        $this->table = null;
        $this->set   = array();

        if (null !== $table) {
            $this->table($table);
        }

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
        $this->table = $table;

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
        foreach ($pairs as $column => $value) {
            $this->set[] = array($column, $value);
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
        $this->set[] = array($column, $value);

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
        if (!is_object($db)) {
            $db = PDO::instance($db);
        }

        $query = 'UPDATE '.$db->quoteTable($this->table);

        $query .= ' SET '.$this->_compile_set($db, $this->set);

        if (!empty($this->where)) {
            $query .= ' WHERE '.$this->_compile_conditions($db, $this->where);
        }

        if (!empty($this->orderBy)) {
            $query .= ' '.$this->_compile_order_by($db, $this->orderBy);
        }

        if ($this->limit !== null) {
            $query .= ' LIMIT '.$this->limit;
        }

        $this->_sql = $query;

        return parent::compile($db);
    }

    public function reset()
    {
        $this->table = null;

        $this->set   = array();
        $this->where = array();

        $this->limit = null;

        $this->_parameters = array();

        $this->_sql = null;

        return $this;
    }
}
