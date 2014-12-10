<?php

namespace Quark\Factory;

interface QueryFactoryInterface
{
    /**
     * @param $columns
     * @return \Quark\Query\Select
     */
    public function createSelectQueryBuilder($columns);

    /**
     * @param array $columns
     * @return \Quark\Query\Select
     */
    public function createSelectArrayQueryBuilder(array $columns);

    /**
     * @param $table
     * @param array $columns
     * @return \Quark\Query\Insert
     */
    public function createInsertQueryBuilder($table, array $columns);

    /**
     * @param $table
     * @return \Quark\Query\Update
     */
    public function createUpdateQueryBuilder($table);

    /**
     * @param $table
     * @return \Quark\Query\Delete
     */
    public function createDeleteQueryBuilder($table);
}