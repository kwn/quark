<?php

namespace Quark\Database;

/**
 * Database expressions can be used to add unescaped SQL fragments to a
 * [Database_Query_Builder] object.
 *
 * For example, you can use an expression to generate a column alias:
 *
 *     // SELECT CONCAT(first_name, last_name) AS full_name
 *     $query = DB::select(array(DB::expr('CONCAT(first_name, last_name)'), 'full_name')));
 *
 * More examples are available on the [Query Builder](database/query/builder#database-expressions) page
 */
class Expression
{
    /**
     * Unquoted parameters
     *
     * @var array
     */
    protected $parameters;

    /**
     * Raw expression string
     *
     * @var string
     */
    protected $value;

    /**
     * Sets the expression string.
     *
     *     $expression = new Expression('COUNT(users.id)');
     *
     * @param   string  $value      raw SQL expression string
     * @param   array   $parameters unquoted parameter values
     * @return  Expression
     */
    public function __construct($value, $parameters = array())
    {
        $this->value = $value;
        $this->parameters = $parameters;
    }

    /**
     * Bind a variable to a parameter.
     *
     * @param   string  $param  parameter key to replace
     * @param   mixed   $var    variable to use
     * @return  $this
     */
    public function bind($param, & $var)
    {
        $this->parameters[$param] =& $var;

        return $this;
    }

    /**
     * Set the value of a parameter.
     *
     * @param   string  $param  parameter key to replace
     * @param   mixed   $value  value to use
     * @return  $this
     */
    public function param($param, $value)
    {
        $this->parameters[$param] = $value;

        return $this;
    }

    /**
     * Add multiple parameter values.
     *
     * @param   array   $params list of parameter values
     * @return  $this
     */
    public function parameters(array $params)
    {
        $this->parameters = $params + $this->parameters;

        return $this;
    }

    /**
     * Get the expression value as a string.
     *
     *     $sql = $expression->value();
     *
     * @return  string
     */
    public function value()
    {
        return (string) $this->value;
    }

    /**
     * Return the value of the expression as a string.
     *
     *     echo $expression;
     *
     * @return  string
     * @uses    Database_Expression::value
     */
    public function __toString()
    {
        return $this->value();
    }

    /**
     * Compile the SQL expression and return it. Replaces any parameters with
     * their given values.
     *
     * @param   mixed    PDO instance or name of instance
     * @return  string
     */
    public function compile($db = NULL)
    {
        if (!is_object($db)) {
            $db = PDO::instance($db);
        }

        $value = $this->value();

        if (!empty($this->parameters)) {
            $params = array_map(array($db, 'quote'), $this->parameters);

            $value = strtr($value, $params);
        }

        return $value;
    }
}
