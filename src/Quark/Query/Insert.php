<?php

namespace Quark\Query;

use Quark\Database\Query\Builder;
use Quark\DB;
use Quark\Exception\QuarkException;

/**
 * Database query builder for INSERT statements. See [Query Builder](/database/query/builder) for usage and examples.
 */
class Insert extends Builder implements QueryInterface
{
    /**
     * INSERT INTO ...
     *
     * @var string|null
     */
    private $table;

    /**
     * (...)
     *
     * @var array
     */
    private $columns;

    /**
     * VALUES (...)
     *
     * @var array
     */
    private $values;

    /**
     * Set the table and columns for an insert.
     *
     * @param  string  $table    table name or object
     * @param  array   $columns  column names
     */
    public function __construct($table = null, array $columns = null)
    {
        $this->table   = null;
        $this->columns = array();
        $this->values  = array();

        if (null !== $table)  {
            $this->table($table);
        }

        if (null !== $columns) {
            $this->columns = $columns;
        }

        return parent::__construct(DB::INSERT, '');
    }

    /**
     * Sets the table to insert into.
     *
     * @param   string $table table name or array($table, $alias) or object
     * @throws  QuarkException
     * @return  $this
     */
    public function table($table)
    {
        if (!is_string($table)) {
            throw new QuarkException('INSERT INTO syntax does not allow table aliasing');
        }

        $this->table = $table;

        return $this;
    }

    /**
     * Set the columns that will be inserted.
     *
     * @param   array  $columns  column names
     * @return  $this
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Adds or overwrites values. Multiple value sets can be added.
     *
     * @param   array $values values list
     * @throws  \Quark\Exception\QuarkException
     * @return  $this
     */
    public function values(array $values)
    {
        if (!is_array($this->values)) {
            throw new QuarkException('INSERT INTO ... SELECT statements cannot be combined with INSERT INTO ... VALUES');
        }

        $values = func_get_args();

        $this->values = array_merge($this->values, $values);

        return $this;
    }

    /**
     * Use a sub-query to for the inserted values.
     *
     * @param \Quark\Database\Query\Builder $query Database_Query of SELECT type
     * @throws \Quark\Exception\QuarkException
     * @return  $this
     */
    public function select(Builder $query)
    {
        if ($query->type() !== DB::SELECT) {
            throw new QuarkException('Only SELECT queries can be combined with INSERT queries');
        }

        $this->values = $query;

        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @return  string
     */
    public function compile()
    {
        $query = 'INSERT INTO '.$this->quoter->quoteTable($this->table);

        $query .= ' ('.implode(', ', array_map(array($this->quoter, 'quoteColumn'), $this->columns)).') ';

        if (is_array($this->values)) {
            $groups = array();
            
            foreach ($this->values as $group) {
                foreach ($group as $offset => $value) {
                    if ((is_string($value))) {
                        $group[$offset] = $this->quoter->quote($value);
                    }
                }

                $groups[] = '('.implode(', ', $group).')';
            }

            $query .= 'VALUES '.implode(', ', $groups);
        } else {
            $query .= (string) $this->values;
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
        $this->table   = null;
        $this->columns = array();
        $this->values  = array();

        $this->sql     = null;

        return $this;
    }
}
