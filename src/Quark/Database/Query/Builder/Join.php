<?php

namespace Quark\Database\Query\Builder;

use Quark\Database\PDO;
use Quark\Database\Query\Builder;
use Quark\Exception\QuarkException;

/**
 * Database query builder for JOIN statements. See [Query Builder](/database/query/builder) for usage and examples.
 */
class Join extends Builder
{
    /**
     * Type of JOIN
     *
     * @var string
     */
    protected $type;

    /**
     * JOIN ...
     *
     * @var mixed
     */
    private $table;

    /**
     * ON ...
     *
     * @var array
     */
    private $on;

    /**
     * USING ...
     *
     * @var array
     */
    private $using;

    /**
     * Creates a new JOIN statement for a table. Optionally, the type of JOIN
     * can be specified as the second parameter.
     *
     * @param  mixed   $table  column name or array($column, $alias) or object
     * @param  string  $type   type of JOIN: INNER, RIGHT, LEFT, etc
     */
    public function __construct($table, $type = null)
    {
        $this->type  = null;
        $this->table = $table;
        $this->on    = array();
        $this->using = array();

        if (null !== $type) {
            $this->type = (string) $type;
        }
    }

    /**
     * Adds a new condition for joining.
     *
     * @param   mixed $c1 column name or array($column, $alias) or object
     * @param   string $op logic operator
     * @param   mixed $c2 column name or array($column, $alias) or object
     * @throws  \Quark\Exception\QuarkException
     * @return  $this
     */
    public function on($c1, $op, $c2)
    {
        if (!empty($this->using)) {
            throw new QuarkException('JOIN ... ON ... cannot be combined with JOIN ... USING ...');
        }

        $this->on[] = array($c1, $op, $c2);

        return $this;
    }

    /**
     * Adds a new condition for joining.
     *
     * @param   string $columns column name
     * @throws  \Quark\Exception\QuarkException
     * @return  $this
     */
    public function using($columns)
    {
        if (!empty($this->on)) {
            throw new QuarkException('JOIN ... ON ... cannot be combined with JOIN ... USING ...');
        }

        $columns = func_get_args();

        $this->using = array_merge($this->using, $columns);

        return $this;
    }

    /**
     * Compile the SQL partial for a JOIN statement and return it.
     *
     * @param   mixed  $db  Database instance or name of instance
     * @return  string
     */
    public function compile($db = null)
    {
        if (!is_object($db)) {
            $db = PDO::instance($db);
        }

        if (null !== $this->type) {
            $sql = strtoupper($this->type).' JOIN';
        } else {
            $sql = 'JOIN';
        }

        $sql .= ' '.$db->quoteTable($this->table);

        if (!empty($this->using)) {
            $sql .= ' USING ('.implode(', ', array_map(array($db, 'quoteColumn'), $this->using)).')';
        } else {
            $conditions = array();

            foreach ($this->on as $condition) {
                list($c1, $op, $c2) = $condition;

                if ($op) {
                    $op = ' '.strtoupper($op);
                }

                $conditions[] = $db->quoteColumn($c1).$op.' '.$db->quoteColumn($c2);
            }

            $sql .= ' ON ('.implode(' AND ', $conditions).')';
        }

        return $sql;
    }

    /**
     * Reset JOIN statement
     *
     * @return $this
     */
    public function reset()
    {
        $this->type  = null;
        $this->table = null;
        $this->on    = array();

        return $this;
    }
}
