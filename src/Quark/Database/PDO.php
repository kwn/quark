<?php

namespace Quark\Database;

use Quark\Database;
use Quark\Exception\QuarkException;
use Quark\Database\Query\Builder;

/**
 * PDO database connection.
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
            'username'   => null,
            'password'   => null,
            'persistent' => false,
        ));

        // Clear the connection parameters for security
        unset($this->_config['connection']);

        // Force PDO to use exceptions for all errors
        $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        if ( ! empty($persistent))
        {
            // Make the connection persistent
            $options[\PDO::ATTR_PERSISTENT] = true;
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
        $this->_connection = null;

        unset(self::$instances[$this->_instance]);

        return true;
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
    public static function instance($name = null, array $config = null)
    {
        if ($name === null)
        {
            $name = self::$default;
        }

        if ( ! isset(self::$instances[$name]))
        {
            if ($config === null)
            {
                // Load the configuration for this database
                $config = array(
                    'type'       => 'PDO',
                    'connection' => array(
                        'dsn'        => 'mysql:host=localhost;dbname=test',
                        'username'   => 'test',
                        'password'   => 'test',
                        'persistent' => false,
                    ),
                    'table_prefix' => '',
                    'charset'      => 'utf8',
                    'caching'      => false,
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
     *     $db->quote(null);   // 'null'
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
        if ($value === null) {
            return 'NULL';
        } elseif ($value === true) {
            return "'1'";
        } elseif ($value === false) {
            return "'0'";
        } elseif (is_object($value)) {
            if ($value instanceof Builder) {
                return '('.$value->compile($this).')';
            } elseif ($value instanceof Expression) {
                return $value->compile($this);
            } else {
                return $this->quote( (string) $value);
            }
        } elseif (is_array($value)) {
            return '('.implode(', ', array_map(array($this, __FUNCTION__), $value)).')';
        } elseif (is_int($value)) {
            return (int) $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }

        return $this->escape($value);
    }

    /**
     * Quote a database column name and add the table prefix if needed.
     *
     *     $column = $db->quoteColumn($column);
     *
     * You can also use SQL methods within identifiers.
     *
     *     $column = $db->quoteColumn(DB::expr('COUNT(`column`)'));
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed   $column  column name or array(column, alias)
     * @return  string
     * @uses    Database::quoteIdentifier
     * @uses    Database::table_prefix
     */
    public function quoteColumn($column)
    {
        $escaped_identifier = $this->_identifier.$this->_identifier;

        if (is_array($column)) {
            list($column, $alias) = $column;
            $alias = str_replace($this->_identifier, $escaped_identifier, $alias);
        }

        if ($column instanceof Builder) {
            $column = '('.$column->compile($this).')';
        } elseif ($column instanceof Expression) {
            $column = $column->compile($this);
        } else {
            $column = (string) $column;
            $column = str_replace($this->_identifier, $escaped_identifier, $column);

            if ($column === '*') {
                return $column;
            } elseif (strpos($column, '.') !== false) {
                $parts = explode('.', $column);

                if ($prefix = $this->table_prefix()) {
                    $offset = count($parts) - 2;

                    $parts[$offset] = $prefix.$parts[$offset];
                }

                foreach ($parts as & $part) {
                    if ($part !== '*') {
                        $part = $this->_identifier.$part.$this->_identifier;
                    }
                }

                $column = implode('.', $parts);
            } else {
                $column = $this->_identifier.$column.$this->_identifier;
            }
        }

        if (isset($alias)) {
            $column .= ' AS '.$this->_identifier.$alias.$this->_identifier;
        }

        return $column;
    }

    /**
     * Quote a database table name and adds the table prefix if needed.
     *
     *     $table = $db->quoteTable($table);
     *
     * Objects passed to this function will be converted to strings.
     * [Database_Expression] objects will be compiled.
     * [Database_Query] objects will be compiled and converted to a sub-query.
     * All other objects will be converted using the `__toString` method.
     *
     * @param   mixed   $table  table name or array(table, alias)
     * @return  string
     * @uses    Database::quoteIdentifier
     * @uses    Database::table_prefix
     */
    public function quoteTable($table)
    {
        $escaped_identifier = $this->_identifier.$this->_identifier;

        if (is_array($table)) {
            list($table, $alias) = $table;
            $alias = str_replace($this->_identifier, $escaped_identifier, $alias);
        }

        if ($table instanceof Builder) {
            $table = '('.$table->compile($this).')';
        } elseif ($table instanceof Expression) {
            $table = $table->compile($this);
        } else {
            $table = (string) $table;
            $table = str_replace($this->_identifier, $escaped_identifier, $table);

            if (strpos($table, '.') !== false) {
                $parts = explode('.', $table);

                if ($prefix = $this->table_prefix()) {
                    $offset = count($parts) - 1;
                    $parts[$offset] = $prefix.$parts[$offset];
                }

                foreach ($parts as & $part) {
                    $part = $this->_identifier.$part.$this->_identifier;
                }

                $table = implode('.', $parts);
            } else {
                $table = $this->_identifier.$this->table_prefix().$table.$this->_identifier;
            }
        }

        if (isset($alias)) {
            $table .= ' AS '.$this->_identifier.$this->table_prefix().$alias.$this->_identifier;
        }

        return $table;
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
        $escaped_identifier = $this->_identifier.$this->_identifier;

        if (is_array($value)) {
            list($value, $alias) = $value;
            $alias = str_replace($this->_identifier, $escaped_identifier, $alias);
        }

        if ($value instanceof Builder) {
            $value = '('.$value->compile($this).')';
        } elseif ($value instanceof Expression) {
            $value = $value->compile($this);
        } else {
            $value = (string) $value;
            $value = str_replace($this->_identifier, $escaped_identifier, $value);

            if (strpos($value, '.') !== false) {
                $parts = explode('.', $value);

                foreach ($parts as & $part) {
                    $part = $this->_identifier.$part.$this->_identifier;
                }

                $value = implode('.', $parts);
            } else {
                $value = $this->_identifier.$value.$this->_identifier;
            }
        }

        if (isset($alias)) {
            $value .= ' AS '.$this->_identifier.$alias.$this->_identifier;
        }

        return $value;
    }
}
