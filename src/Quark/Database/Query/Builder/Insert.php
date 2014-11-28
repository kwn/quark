<?php

namespace Quark\Database\Query\Builder;

use Quark\Database\PDO;
use Quark\Database\Query\Builder;
use Quark\DB;
use Quark\Exception\QuarkException;

/**
 * Database query builder for INSERT statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Insert extends Builder
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
    private $columns = array();

    /**
     * VALUES (...)
     *
     * @var array
     */
    private $values = array();

    /**
     * Set the table and columns for an insert.
     *
     * @param   mixed  $table    table name or array($table, $alias) or object
     * @param   array  $columns  column names
     */
    public function __construct($table = null, array $columns = null)
    {
        if ($table)
        {
            // Set the inital table name
            $this->table($table);
        }

        if ($columns)
        {
            // Set the column names
            $this->columns = $columns;
        }

        // Start the query with no SQL
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
     * @throws \Quark\Exception\QuarkException
     * @return  $this
     */
    public function values(array $values)
    {
        if ( ! is_array($this->values))
        {
            throw new QuarkException('INSERT INTO ... SELECT statements cannot be combined with INSERT INTO ... VALUES');
        }

        // Get all of the passed values
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
        if ($query->type() !== DB::SELECT)
        {
            throw new QuarkException('Only SELECT queries can be combined with INSERT queries');
        }

        $this->values = $query;

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

        // Start an insertion query
        $query = 'INSERT INTO '.$db->quote_table($this->table);

        // Add the column names
        $query .= ' ('.implode(', ', array_map(array($db, 'quote_column'), $this->columns)).') ';

        if (is_array($this->values))
        {
            // Callback for quoting values
            $quote = array($db, 'quote');

            $groups = array();
            foreach ($this->values as $group)
            {
                foreach ($group as $offset => $value)
                {
                    if ((is_string($value) && array_key_exists($value, $this->_parameters)) === FALSE)
                    {
                        // Quote the value, it is not a parameter
                        $group[$offset] = $db->quote($value);
                    }
                }

                $groups[] = '('.implode(', ', $group).')';
            }

            // Add the values
            $query .= 'VALUES '.implode(', ', $groups);
        }
        else
        {
            // Add the sub-query
            $query .= (string) $this->values;
        }

        $this->_sql = $query;

        return parent::compile($db);
    }

    public function reset()
    {
        $this->table = null;

        $this->columns = array();
        $this->values  = array();

        $this->_parameters = array();

        $this->_sql = null;

        return $this;
    }
}
