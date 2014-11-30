<?php

namespace Quark\Database\Query;

use Quark\Database\PDO;
use Quark\Database\Query\Builder\Join;

/**
 * Database query builder. See [Query Builder](/database/query/builder) for usage and examples.
 */
abstract class Builder
{
    /**
     * Query type
     *
     * @var int
     */
    protected $type;

    /**
     * SQL statement
     *
     * @var string
     */
    protected $sql;

    /**
     * Creates a new SQL query of the specified type.
     *
     * @param   integer  $type  query type: Database::SELECT, Database::INSERT, etc
     * @param   string   $sql   query string
     */
    public function __construct($type, $sql)
    {
        $this->type       = $type;
        $this->sql        = $sql;
    }

    /**
     * Return the SQL query string.
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            return $this->compile(PDO::instance());
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get the type of the query.
     *
     * @return  integer
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Compile the SQL query and return it. Replaces any parameters with their
     * given values.
     *
     * @param  mixed $db Database instance or name of instance
     * @return string
     */
    public function compile($db = null)
    {
        $sql = $this->sql;

        return $sql;
    }

    /**
     * Compiles an array of JOIN statements into an SQL partial.
     *
     * @param \Quark\Database\PDO $db Database instance
     * @param   array $joins join statements
     * @return  string
     */
    protected function compileJoin(PDO $db, array $joins)
    {
        $statements = array();

        foreach ($joins as $join) {
            /** @var Join $join */
            $statements[] = $join->compile($db);
        }

        return implode(' ', $statements);
    }

    /**
     * Compiles an array of conditions into an SQL partial. Used for WHERE
     * and HAVING.
     *
     * @param  \Quark\Database\PDO $db Database instance
     * @param  array $conditions condition statements
     * @return string
     */
    protected function compileConditions(PDO $db, array $conditions)
    {
        $last_condition = null;

        $sql = '';

        foreach ($conditions as $group) {
            foreach ($group as $logic => $condition) {
                if ($condition === '(') {
                    if (!empty($sql) && $last_condition !== '(') {
                        $sql .= ' ' . $logic . ' ';
                    }

                    $sql .= '(';
                } elseif ($condition === ')') {
                    $sql .= ')';
                } else {
                    if (!empty($sql) && $last_condition !== '(') {
                        $sql .= ' ' . $logic . ' ';
                    }

                    list($column, $op, $value) = $condition;

                    if ($value === null) {
                        if ($op === '=') {
                            $op = 'IS';
                        } elseif ($op === '!=') {
                            $op = 'IS NOT';
                        }
                    }

                    $op = strtoupper($op);

                    if ($op === 'BETWEEN' && is_array($value)) {
                        list($min, $max) = $value;

                        $min = $db->quote($min);
                        $max = $db->quote($max);

                        $value = $min . ' AND ' . $max;
                    } else {
                        $value = $db->quote($value);
                    }

                    if ($column) {
                        if (is_array($column)) {
                            $column = $db->quoteIdentifier(reset($column));
                        } else {
                            $column = $db->quoteColumn($column);
                        }
                    }

                    $sql .= trim($column . ' ' . $op . ' ' . $value);
                }

                $last_condition = $condition;
            }
        }

        return $sql;
    }

    /**
     * Compiles an array of set values into an SQL partial. Used for UPDATE.
     *
     * @param  \Quark\Database\PDO $db Database instance
     * @param  array $values updated values
     * @return string
     */
    protected function compileSet(PDO $db, array $values)
    {
        $set = array();

        foreach ($values as $group) {
            list ($column, $value) = $group;

            $column = $db->quoteColumn($column);
            $value  = $db->quote($value);

            $set[$column] = $column . ' = ' . $value;
        }

        return implode(', ', $set);
    }

    /**
     * Compiles an array of GROUP BY columns into an SQL partial.
     *
     * @param  \Quark\Database\PDO $db Database instance
     * @param  array $columns
     * @return string
     */
    protected function compileGroupBy(PDO $db, array $columns)
    {
        $group = array();

        foreach ($columns as $column) {
            if (is_array($column)) {
                $column = $db->quoteIdentifier(end($column));
            } else {
                $column = $db->quoteColumn($column);
            }

            $group[] = $column;
        }

        return 'GROUP BY ' . implode(', ', $group);
    }

    /**
     * Compiles an array of ORDER BY statements into an SQL partial.
     *
     * @param  \Quark\Database\PDO $db Database instance
     * @param  array $columns sorting columns
     * @return string
     */
    protected function compileOrderBy(PDO $db, array $columns)
    {
        $sort = array();

        foreach ($columns as $group) {
            list ($column, $direction) = $group;

            if (is_array($column)) {
                $column = $db->quoteIdentifier(end($column));
            } else {
                $column = $db->quoteColumn($column);
            }

            if ($direction) {
                $direction = ' ' . strtoupper($direction);
            }

            $sort[] = $column . $direction;
        }

        return 'ORDER BY ' . implode(', ', $sort);
    }

    /**
     * Reset the current builder status.
     *
     * @return $this
     */
    abstract public function reset();
}
