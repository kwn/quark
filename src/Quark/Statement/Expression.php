<?php

namespace Quark\Statement;

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
     * @return  Expression
     */
    public function __construct($value)
    {
        $this->value = $value;
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
     * Compile the SQL expression and return it.
     *
     * @return  string
     */
    public function compile()
    {
        $value = $this->value();

        return $value;
    }
}
