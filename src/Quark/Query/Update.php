<?php

namespace Quark\Query;

use Quark\Statement\Where;

/**
 * Database query builder for UPDATE statements. See [Query Builder](/database/query/builder) for usage and examples.
 */
class Update extends Where implements QueryInterface
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
        $this->table   = null;
        $this->set     = array();

        $this->where   = array();
        $this->orderBy = array();
        $this->limit   = null;

        if (null !== $table) {
            $this->table($table);
        }

        return parent::__construct('');
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
     * @return  string
     */
    public function compile()
    {
        $query = 'UPDATE '.$this->quoter->quoteTable($this->table);

        $query .= ' SET '.$this->compileSet($this->set);

        if (!empty($this->where)) {
            $query .= ' WHERE '.$this->compileConditions($this->where);
        }

        if (!empty($this->orderBy)) {
            $query .= ' '.$this->compileOrderBy($this->orderBy);
        }

        if ($this->limit !== null) {
            $query .= ' LIMIT '.$this->limit;
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
        $this->table = null;
        $this->set   = array();

        $this->where = array();
        $this->limit = null;

        $this->sql   = null;

        return $this;
    }
}
