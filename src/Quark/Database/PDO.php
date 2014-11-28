<?php

namespace Quark\Database;

use Quark\Database;
use Quark\Exception\QuarkException;
use Quark\Database\Query\Builder;

/**
 * PDO database connection.
 *
 * @package    Kohana/Database
 * @category   Drivers
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class PDO
{
    // Instance name
    protected $_instance;

    // Raw server connection
    protected $_connection;

    // Configuration array
    protected $_config;

    // PDO uses no quoting for identifiers
    protected $_identifier = '';

    /**
     * @var  string  default instance name
     */
    public static $default = 'default';

    /**
     * @var  array  Database instances
     */
    public static $instances = array();

    /**
     * Stores the database configuration locally and name the instance.
     *
     * [!!] This method cannot be accessed directly, you must use [Database::instance].
     */
    public function __construct($name, array $config)
    {
        // Set the instance name
        $this->_instance = $name;

        // Store the config locally
        $this->_config = $config;

        if (empty($this->_config['table_prefix']))
        {
            $this->_config['table_prefix'] = '';
        }

        if (isset($this->_config['identifier']))
        {
            // Allow the identifier to be overloaded per-connection
            $this->_identifier = (string) $this->_config['identifier'];
        }
    }

    /**
     * Disconnect from the database when the object is destroyed.
     *
     *     // Destroy the database instance
     *     unset(Database::instances[(string) $db], $db);
     *
     * [!!] Calling `unset($db)` is not enough to destroy the database, as it
     * will still be stored in `Database::$instances`.
     *
     * @return  void
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Returns the database instance name.
     *
     *     echo (string) $db;
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->_instance;
    }

    /**
     * Connect to the database. This is called automatically when the first
     * query is executed.
     *
     *     $db->connect();
     *
     * @throws  Exception
     * @return  void
     */
    public function connect()
    {
        if ($this->_connection)
            return;

        // Extract the connection parameters, adding required variabels
        extract($this->_config['connection'] + array(
            'dsn'        => '',
            'username'   => NULL,
            'password'   => NULL,
            'persistent' => FALSE,
        ));

        // Clear the connection parameters for security
        unset($this->_config['connection']);

        // Force PDO to use exceptions for all errors
        $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        if ( ! empty($persistent))
        {
            // Make the connection persistent
            $options[\PDO::ATTR_PERSISTENT] = TRUE;
        }

        try
        {
            // Create a new PDO connection
            $this->_connection = new \PDO($dsn, $username, $password, $options);
        }
        catch (\PDOException $e)
        {
            throw new QuarkException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Disconnect from the database. This is called automatically by [Database::__destruct].
     * Clears the database instance from [Database::$instances].
     *
     *     $db->disconnect();
     *
     * @return  boolean
     */
    public function disconnect()
    {
        // Destroy the PDO object
        $this->_connection = NULL;

        unset(self::$instances[$this->_instance]);

        return TRUE;
    }

    /**
     * Sanitize a string by escaping characters that could cause an SQL
     * injection attack.
     *
     *     $value = $db->escape('any string');
     *
     * @param   string   $value  value to quote
     * @return  string
     */
    public function escape($value)
    {
        // Make sure the database is connected
        $this->_connection or $this->connect();

        return $this->_connection->quote($value);
    }

    /**
     * Get a singleton Database instance. If configuration is not specified,
     * it will be loaded from the database configuration file using the same
     * group as the name.
     *
     *     // Load the default database
     *     $db = Database::instance();
     *
     *     // Create a custom configured instance
     *     $db = Database::instance('custom', $config);
     *
     * @param   string $name instance name
     * @param   array $config configuration parameters
     * @throws  \Exception
     * @return  PDO
     */
    public static function instance($name = NULL, array $config = NULL)
    {
        if ($name === NULL)
        {
            // Use the default instance name
            $name = self::$default;
        }

        if ( ! isset(self::$instances[$name]))
        {
            if ($config === NULL)
            {
                // Load the configuration for this database
                $config = array(
                    'type'       => 'PDO',
                    'connection' => array(
                        'dsn'        => 'mysql:host=localhost;dbname=eudeco',
                        'username'   => 'root',
                        'password'   => 'Panties69',
                        'persistent' => FALSE,
                    ),
                    'table_prefix' => '',
                    'charset'      => 'utf8',
                    'caching'      => FALSE,
                );
            }

            if ( ! isset($config['type']))
            {
                throw new \Exception(sprintf('Database type not defined in :name configuration', $name));
            }

            // Set the driver class name
            $driver = 'Quark\\Database\\'.ucfirst($config['type']);

            // Create the database connection instance
            $driver = new $driver($name, $config);

            // Store the database instance
            self::$instances[$name] = $driver;
        }

        return self::$instances[$name];
    }

    /**
     * Extracts the text between parentheses, if any.
     *
     *     // Returns: array('CHAR', '6')
     *     list($type, $length) = $db->_parse_type('CHAR(6)');
     *
     * @param   string  $type
     * @return  array   list containing the type and length, if any
     */
    protected function _parse_type($type)
    {
        if (($open = strpos($type, '(')) === FALSE)
        {
            // No length specified
            return array($type, NULL);
        }

        // Closing parenthesis
        $close = strrpos($type, ')', $open);

        // Length without parentheses
        $length = substr($type, $open + 1, $close - 1 - $open);

        // Type without the length
        $type = substr($type, 0, $open).substr($type, $close + 1);

        return array($type, $length);
    }

    /**
     * Return the table prefix defined in the current configuration.
     *
     *     $prefix = $db->table_prefix();
     *
     * @return  string
     */
    public function table_prefix()
    {
        return $this->_config['table_prefix'];
    }

    /**
     * Quote a value for an SQL query.
     *
     *     $db->quote(NULL);   // 'NULL'
     *     $db->quote(10);     // 10
     *     $db->quote('fred'); // 'fred'
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed   $value  any value to quote
     * @return  string
     * @uses    Database::escape
     */
    public function quote($value)
    {
        if ($value === NULL)
        {
            return 'NULL';
        }
        elseif ($value === TRUE)
        {
            return "'1'";
        }
        elseif ($value === FALSE)
        {
            return "'0'";
        }
        elseif (is_object($value))
        {
            if ($value instanceof Builder)
            {
                // Create a sub-query
                return '('.$value->compile($this).')';
            }
            elseif ($value instanceof Expression)
            {
                // Compile the expression
                return $value->compile($this);
            }
            else
            {
                // Convert the object to a string
                return $this->quote( (string) $value);
            }
        }
        elseif (is_array($value))
        {
            return '('.implode(', ', array_map(array($this, __FUNCTION__), $value)).')';
        }
        elseif (is_int($value))
        {
            return (int) $value;
        }
        elseif (is_float($value))
        {
            // Convert to non-locale aware float to prevent possible commas
            return sprintf('%F', $value);
        }

        return $this->escape($value);
    }

    /**
     * @param $column
     * @return string
     * @deprecated
     */
    public function quote_column($column)
    {
        return $this->quoteColumn($column);
    }

    /**
     * Quote a database column name and add the table prefix if needed.
     *
     *     $column = $db->quote_column($column);
     *
     * You can also use SQL methods within identifiers.
     *
     *     $column = $db->quote_column(DB::expr('COUNT(`column`)'));
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed   $column  column name or array(column, alias)
     * @return  string
     * @uses    Database::quote_identifier
     * @uses    Database::table_prefix
     */
    public function quoteColumn($column)
    {
        // Identifiers are escaped by repeating them
        $escaped_identifier = $this->_identifier.$this->_identifier;

        if (is_array($column))
        {
            list($column, $alias) = $column;
            $alias = str_replace($this->_identifier, $escaped_identifier, $alias);
        }

        if ($column instanceof Builder)
        {
            // Create a sub-query
            $column = '('.$column->compile($this).')';
        }
        elseif ($column instanceof Expression)
        {
            // Compile the expression
            $column = $column->compile($this);
        }
        else
        {
            // Convert to a string
            $column = (string) $column;

            $column = str_replace($this->_identifier, $escaped_identifier, $column);

            if ($column === '*')
            {
                return $column;
            }
            elseif (strpos($column, '.') !== FALSE)
            {
                $parts = explode('.', $column);

                if ($prefix = $this->table_prefix())
                {
                    // Get the offset of the table name, 2nd-to-last part
                    $offset = count($parts) - 2;

                    // Add the table prefix to the table name
                    $parts[$offset] = $prefix.$parts[$offset];
                }

                foreach ($parts as & $part)
                {
                    if ($part !== '*')
                    {
                        // Quote each of the parts
                        $part = $this->_identifier.$part.$this->_identifier;
                    }
                }

                $column = implode('.', $parts);
            }
            else
            {
                $column = $this->_identifier.$column.$this->_identifier;
            }
        }

        if (isset($alias))
        {
            $column .= ' AS '.$this->_identifier.$alias.$this->_identifier;
        }

        return $column;
    }

    /**
     * @param $table
     * @return string
     * @deprecated
     */
    public function quote_table($table)
    {
        return $this->quoteTable($table);
    }

    /**
     * Quote a database table name and adds the table prefix if needed.
     *
     *     $table = $db->quote_table($table);
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed   $table  table name or array(table, alias)
     * @return  string
     * @uses    Database::quote_identifier
     * @uses    Database::table_prefix
     */
    public function quoteTable($table)
    {
        // Identifiers are escaped by repeating them
        $escaped_identifier = $this->_identifier.$this->_identifier;

        if (is_array($table))
        {
            list($table, $alias) = $table;
            $alias = str_replace($this->_identifier, $escaped_identifier, $alias);
        }

        if ($table instanceof Builder)
        {
            // Create a sub-query
            $table = '('.$table->compile($this).')';
        }
        elseif ($table instanceof Expression)
        {
            // Compile the expression
            $table = $table->compile($this);
        }
        else
        {
            // Convert to a string
            $table = (string) $table;

            $table = str_replace($this->_identifier, $escaped_identifier, $table);

            if (strpos($table, '.') !== FALSE)
            {
                $parts = explode('.', $table);

                if ($prefix = $this->table_prefix())
                {
                    // Get the offset of the table name, last part
                    $offset = count($parts) - 1;

                    // Add the table prefix to the table name
                    $parts[$offset] = $prefix.$parts[$offset];
                }

                foreach ($parts as & $part)
                {
                    // Quote each of the parts
                    $part = $this->_identifier.$part.$this->_identifier;
                }

                $table = implode('.', $parts);
            }
            else
            {
                // Add the table prefix
                $table = $this->_identifier.$this->table_prefix().$table.$this->_identifier;
            }
        }

        if (isset($alias))
        {
            // Attach table prefix to alias
            $table .= ' AS '.$this->_identifier.$this->table_prefix().$alias.$this->_identifier;
        }

        return $table;
    }

    /**
     * @param $value
     * @return string
     * @deprecated
     */
    public function quote_identifier($value)
    {
        return $this->quoteIdentifier($value);
    }

    /**
     * Quote a database identifier
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed   $value  any identifier
     * @return  string
     */
    public function quoteIdentifier($value)
    {
        // Identifiers are escaped by repeating them
        $escaped_identifier = $this->_identifier.$this->_identifier;

        if (is_array($value))
        {
            list($value, $alias) = $value;
            $alias = str_replace($this->_identifier, $escaped_identifier, $alias);
        }

        if ($value instanceof Builder)
        {
            // Create a sub-query
            $value = '('.$value->compile($this).')';
        }
        elseif ($value instanceof Expression)
        {
            // Compile the expression
            $value = $value->compile($this);
        }
        else
        {
            // Convert to a string
            $value = (string) $value;

            $value = str_replace($this->_identifier, $escaped_identifier, $value);

            if (strpos($value, '.') !== FALSE)
            {
                $parts = explode('.', $value);

                foreach ($parts as & $part)
                {
                    // Quote each of the parts
                    $part = $this->_identifier.$part.$this->_identifier;
                }

                $value = implode('.', $parts);
            }
            else
            {
                $value = $this->_identifier.$value.$this->_identifier;
            }
        }

        if (isset($alias))
        {
            $value .= ' AS '.$this->_identifier.$alias.$this->_identifier;
        }

        return $value;
    }
}
