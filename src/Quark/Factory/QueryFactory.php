<?php

namespace Quark\Factory;

use Quark\Query\Delete;
use Quark\Query\Insert;
use Quark\Query\Select;
use Quark\Query\Update;

class QueryFactory implements QueryFactoryInterface
{
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
    public function createSelectQueryBuilder($columns = null)
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
    public function createSelectArrayQueryBuilder(array $columns = null)
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
    public function createInsertQueryBuilder($table = null, array $columns = null)
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
    public function createUpdateQueryBuilder($table = null)
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
    public function createDeleteQueryBuilder($table = null)
    {
        return new Delete($table);
    }
}