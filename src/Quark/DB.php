<?php

namespace Quark;

use Quark\Query\Delete;
use Quark\Query\Insert;
use Quark\Query\Select;
use Quark\Query\Update;
use Quark\Statement\Expression;

/**
 * Provides a shortcut to get Database related objects for [making queries](../database/query).
 *
 * Shortcut     | Returned Object
 * -------------|---------------
 * [`DB::query()`](#query)   | [Database_Query]
 * [`DB::insert()`](#insert) | [Database_Query_Builder_Insert]
 * [`DB::select()`](#select),<br />[`DB::select_array()`](#select_array) | [Database_Query_Builder_Select]
 * [`DB::update()`](#update) | [Database_Query_Builder_Update]
 * [`DB::delete()`](#delete) | [Database_Query_Builder_Delete]
 * [`DB::expr()`](#expr)     | [Database_Expression]
 *
 * You pass the same parameters to these functions as you pass to the objects they return.
 *
 * @package    Kohana/Database
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class DB
{
    // Query types
    const SELECT =  1;
    const INSERT =  2;
    const UPDATE =  3;
    const DELETE =  4;

    /**
     * Create a new [Database_Query_Builder_Select]. Each argument will be
     * treated as a column. To generate a `foo AS bar` alias, use an array.
     *
     *     // SELECT id, username
     *     $query = DB::select('id', 'username');
     *
     *     // SELECT id AS user_id
     *     $query = DB::select(array('id', 'user_id'));
     *
     * @param   mixed   $columns  column name or array($column, $alias) or object
     * @return  Select
     */
    public static function select($columns = null)
    {
        return new Select(func_get_args());
    }

    /**
     * Create a new [Database_Query_Builder_Select] from an array of columns.
     *
     *     // SELECT id, username
     *     $query = DB::select_array(array('id', 'username'));
     *
     * @param   array   $columns  columns to select
     * @return  Select
     */
    public static function select_array(array $columns = null)
    {
        return new Select($columns);
    }

    /**
     * Create a new [Database_Query_Builder_Insert].
     *
     *     // INSERT INTO users (id, username)
     *     $query = DB::insert('users', array('id', 'username'));
     *
     * @param   string  $table    table to insert into
     * @param   array   $columns  list of column names or array($column, $alias) or object
     * @return  Insert
     */
    public static function insert($table = null, array $columns = null)
    {
        return new Insert($table, $columns);
    }

    /**
     * Create a new [Database_Query_Builder_Update].
     *
     *     // UPDATE users
     *     $query = DB::update('users');
     *
     * @param   string  $table  table to update
     * @return  Update
     */
    public static function update($table = null)
    {
        return new Update($table);
    }

    /**
     * Create a new [Database_Query_Builder_Delete].
     *
     *     // DELETE FROM users
     *     $query = DB::delete('users');
     *
     * @param   string  $table  table to delete from
     * @return  Delete
     */
    public static function delete($table = null)
    {
        return new Delete($table);
    }

    /**
     * Create a new [Database_Expression] which is not escaped. An expression
     * is the only way to use SQL functions within query builders.
     *
     *     $expression = DB::expr('COUNT(users.id)');
     *     $query = DB::update('users')->set(array('login_count' => DB::expr('login_count + 1')))->where('id', '=', $id);
     *     $users = ORM::factory('user')->where(DB::expr("BINARY `hash`"), '=', $hash)->find();
     *
     * @param   string  $string  expression
     * @param   array   $parameters
     * @return  Expression
     */
    public static function expr($string, $parameters = array())
    {
        return new Expression($string, $parameters);
    }
}
