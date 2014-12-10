<?php

namespace Quark\Worker;

use Quark\Statement\Join;

/**
 * Database query builder. See [Query Builder](/database/query/builder) for usage and examples.
 */
abstract class Builder
{
    /**
     * SQL statement
     *
     * @var string
     */
    protected $sql;

    /**
     * @var \Quark\Worker\Quoter
     */
    protected $quoter;

    /**
     * Creates a new SQL query of the specified type.
     *
     * @param   string   $sql   query string
     */
    public function __construct($sql)
    {
        $this->sql    = $sql;
        $this->quoter = Quoter::instance();
    }

    /**
     * Return the SQL query string.
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            return $this->compile();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Compile the SQL query and return it. Replaces any parameters with their
     * given values.
     *
     * @return string
     */
    public function compile()
    {
        $sql = $this->sql;

        return $sql;
    }

    /**
     * Compiles an array of JOIN statements into an SQL partial.
     *
     * @param   array $joins join statements
     * @return  string
     */
    protected function compileJoin(array $joins)
    {
        $statements = array();

        foreach ($joins as $join) {
            /** @var Join $join */
            $statements[] = $join->compile();
        }

        return implode(' ', $statements);
    }

    /**
     * Compiles an array of conditions into an SQL partial. Used for WHERE
     * and HAVING.
     *
     * @param  array $conditions condition statements
     * @return string
     */
    protected function compileConditions(array $conditions)
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

                        $min = $this->quoter->quote($min);
                        $max = $this->quoter->quote($max);

                        $value = $min . ' AND ' . $max;
                    } else {
                        $value = $this->quoter->quote($value);
                    }

                    if ($column) {
                        if (is_array($column)) {
                            $column = $this->quoter->quoteIdentifier(reset($column));
                        } else {
                            $column = $this->quoter->quoteColumn($column);
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
     * @param  array $values updated values
     * @return string
     */
    protected function compileSet(array $values)
    {
        $set = array();

        foreach ($values as $group) {
            list ($column, $value) = $group;

            $column = $this->quoter->quoteColumn($column);
            $value  = $this->quoter->quote($value);

            $set[$column] = $column . ' = ' . $value;
        }

        return implode(', ', $set);
    }

    /**
     * Compiles an array of GROUP BY columns into an SQL partial.
     *
     * @param  array $columns
     * @return string
     */
    protected function compileGroupBy(array $columns)
    {
        $group = array();

        foreach ($columns as $column) {
            if (is_array($column)) {
                $column = $this->quoter->quoteIdentifier(end($column));
            } else {
                $column = $this->quoter->quoteColumn($column);
            }

            $group[] = $column;
        }

        return 'GROUP BY ' . implode(', ', $group);
    }

    /**
     * Compiles an array of ORDER BY statements into an SQL partial.
     *
     * @param  array $columns sorting columns
     * @return string
     */
    protected function compileOrderBy(array $columns)
    {
        $sort = array();

        foreach ($columns as $group) {
            list ($column, $direction) = $group;

            if (is_array($column)) {
                $column = $this->quoter->quoteIdentifier(end($column));
            } else {
                $column = $this->quoter->quoteColumn($column);
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
