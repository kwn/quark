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
    protected $_table;

    /**
     * (...)
     *
     * @var array
     */
    protected $_columns = array();

    /**
     * VALUES (...)
     *
     * @var array
     */
    protected $_values = array();

    /**
     * Set the table and columns for an insert.
     *
     * @param   mixed  $table    table name or array($table, $alias) or object
     * @param   array  $columns  column names
     */
    public function __construct($table = NULL, array $columns = NULL)
    {
        if ($table)
        {
            // Set the inital table name
            $this->_table = $table;
        }

        if ($columns)
        {
            // Set the column names
            $this->_columns = $columns;
        }

        // Start the query with no SQL
        return parent::__construct(DB::INSERT, '');
    }

    /**
     * Sets the table to insert into.
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
     * Set the columns that will be inserted.
     *
     * @param   array  $columns  column names
     * @return  $this
     */
    public function columns(array $columns)
    {
        $this->_columns = $columns;

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
        if ( ! is_array($this->_values))
        {
            throw new QuarkException('INSERT INTO ... SELECT statements cannot be combined with INSERT INTO ... VALUES');
        }

        // Get all of the passed values
        $values = func_get_args();

        $this->_values = array_merge($this->_values, $values);

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

        $this->_values = $query;

        return $this;
    }

    /**
     * Compile the SQL query and return it.
     *
     * @param   mixed  $db  Database instance or name of instance
     * @return  string
     */
    public function compile($db = NULL)
    {
        if ( ! is_object($db))
        {
            // Get the database instance
            $db = PDO::instance($db);
        }

        // Start an insertion query
        $query = 'INSERT INTO '.$db->quote_table($this->_table);

        // Add the column names
        $query .= ' ('.implode(', ', array_map(array($db, 'quote_column'), $this->_columns)).') ';

        if (is_array($this->_values))
        {
            // Callback for quoting values
            $quote = array($db, 'quote');

            $groups = array();
            foreach ($this->_values as $group)
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
            if (is_array($this->_table)) {
                throw new QuarkException('Cannot use table alias with INSERT INTO ... SELECT ... construction');
            }
            // Add the sub-query
            $query .= (string) $this->_values;
        }

        $this->_sql = $query;

        return parent::compile($db);
    }

    public function reset()
    {
        $this->_table = NULL;

        $this->_columns =
        $this->_values  = array();

        $this->_parameters = array();

        $this->_sql = NULL;

        return $this;
    }
}
