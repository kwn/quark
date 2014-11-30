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
     * Quoted query parameters
     *
     * @var array
     */
    protected $parameters;

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
        $this->parameters = array();
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
     * Set the value of a parameter in the query.
     *
     * @param   string   $param  parameter key to replace
     * @param   mixed    $value  value to use
     * @return  $this
     */
    public function param($param, $value)
    {
        // Add or overload a new parameter
        $this->parameters[$param] = $value;

        return $this;
    }

    /**
     * Bind a variable to a parameter in the query.
     *
     * @param   string  $param  parameter key to replace
     * @param   mixed   $var    variable to use
     * @return  $this
     */
    public function bind($param, & $var)
    {
        // Bind a value to a variable
        $this->parameters[$param] =& $var;

        return $this;
    }

    /**
     * Add multiple parameters to the query.
     *
     * @param   array  $params  list of parameters
     * @return  $this
     */
    public function parameters(array $params)
    {
        // Merge the new parameters in
        $this->parameters = $params + $this->parameters;

        return $this;
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
        if (!is_object($db)) {
            $db = PDO::instance($db);
        }

        $sql = $this->sql;

        if (!empty($this->parameters)) {
            $values = array_map(array($db, 'quote'), $this->parameters);

            $sql = strtr($sql, $values);
        }

        return $sql;
    }

    /**
     * Compiles an array of JOIN statements into an SQL partial.
     *
     * @param \Quark\Database\PDO $db Database instance
     * @param   array $joins join statements
     * @return  string
     */
    protected function _compile_join(PDO $db, array $joins)
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
    protected function _compile_conditions(PDO $db, array $conditions)
    {
        $last_condition = null;

        $sql = '';

        foreach ($conditions as $group) {
            // Process groups of conditions
            foreach ($group as $logic => $condition) {
                if ($condition === '(') {
                    if (!empty($sql) && $last_condition !== '(') {
                        // Include logic operator
                        $sql .= ' ' . $logic . ' ';
                    }

                    $sql .= '(';
                } elseif ($condition === ')') {
                    $sql .= ')';
                } else {
                    if (!empty($sql) && $last_condition !== '(') {
                        // Add the logic operator
                        $sql .= ' ' . $logic . ' ';
                    }

                    // Split the condition
                    list($column, $op, $value) = $condition;

                    if ($value === null) {
                        if ($op === '=') {
                            // Convert "val = null" to "val IS null"
                            $op = 'IS';
                        } elseif ($op === '!=') {
                            // Convert "val != null" to "valu IS NOT null"
                            $op = 'IS NOT';
                        }
                    }

                    // Database operators are always uppercase
                    $op = strtoupper($op);

                    if ($op === 'BETWEEN' && is_array($value)) {
                        // BETWEEN always has exactly two arguments
                        list($min, $max) = $value;

                        if ((is_string($min) && array_key_exists($min, $this->parameters)) === false) {
                            // Quote the value, it is not a parameter
                            $min = $db->quote($min);
                        }

                        if ((is_string($max) && array_key_exists($max, $this->parameters)) === false) {
                            // Quote the value, it is not a parameter
                            $max = $db->quote($max);
                        }

                        // Quote the min and max value
                        $value = $min . ' AND ' . $max;
                    } elseif ((is_string($value) && array_key_exists($value, $this->parameters)) === false) {
                        // Quote the value, it is not a parameter
                        $value = $db->quote($value);
                    }

                    if ($column) {
                        if (is_array($column)) {
                            // Use the column name
                            $column = $db->quoteIdentifier(reset($column));
                        } else {
                            // Apply proper quoting to the column
                            $column = $db->quoteColumn($column);
                        }
                    }

                    // Append the statement to the query
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
    protected function _compile_set(PDO $db, array $values)
    {
        $set = array();

        foreach ($values as $group) {
            // Split the set
            list ($column, $value) = $group;

            // Quote the column name
            $column = $db->quoteColumn($column);

            if ((is_string($value) && array_key_exists($value, $this->parameters)) === false) {
                // Quote the value, it is not a parameter
                $value = $db->quote($value);
            }

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
    protected function _compile_group_by(PDO $db, array $columns)
    {
        $group = array();

        foreach ($columns as $column) {
            if (is_array($column)) {
                // Use the column alias
                $column = $db->quoteIdentifier(end($column));
            } else {
                // Apply proper quoting to the column
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
    protected function _compile_order_by(PDO $db, array $columns)
    {
        $sort = array();

        foreach ($columns as $group) {
            list ($column, $direction) = $group;

            if (is_array($column)) {
                // Use the column alias
                $column = $db->quoteIdentifier(end($column));
            } else {
                // Apply proper quoting to the column
                $column = $db->quoteColumn($column);
            }

            if ($direction) {
                // Make the direction uppercase
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
