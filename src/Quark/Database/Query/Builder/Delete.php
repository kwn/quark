<?php

namespace Quark\Database\Query\Builder;

use Quark\Database\PDO;
use Quark\DB;
use Quark\Exception\QuarkException;

/**
 * Database query builder for DELETE statements. See [Query Builder](/database/query/builder) for usage and examples.
 */
class Delete extends Where
{
    /**
     * DELETE FROM ...
     *
     * @var string|null
     */
    private $table;

    /**
     * Set the table for a delete.
     *
     * @param   string  $table  table name or array($table, $alias) or object
     */
    public function __construct($table = null)
    {
        $this->table = null;

        if (null !== $table) {
            $this->table($table);
        }

        return parent::__construct(DB::DELETE, '');
    }

    /**
     * Sets the table to delete from.
     *
     * @param   mixed $table table name or array($table, $alias) or object
     * @throws  QuarkException
     * @return  $this
     */
    public function table($table)
    {
        if (!is_string($table)) {
            throw new QuarkException('DELETE FROM syntax does not allow table aliasing');
        }

        $this->table = $table;

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

        $query = 'DELETE FROM '.$db->quoteTable($this->table);

        if (!empty($this->where)) {
            $query .= ' WHERE '.$this->_compile_conditions($db, $this->where);
        }

        if (!empty($this->orderBy)) {
            $query .= ' '.$this->_compile_order_by($db, $this->orderBy);
        }

        if (null !== $this->limit) {
            $query .= ' LIMIT '.$this->limit;
        }

        $this->_sql = $query;

        return parent::compile($db);
    }

    public function reset()
    {
        $this->table = null;
        $this->where = array();

        $this->_parameters = array();
        $this->_sql        = null;

        return $this;
    }
}
