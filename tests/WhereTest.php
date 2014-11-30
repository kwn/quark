<?php

class WhereTest extends PHPUnit_Framework_TestCase
{
    public function testWhereSimpleQuery()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->where_open()
                ->where('u.age', '>', 18)
                ->or_where('u.adult', '=', true)
            ->where_close()
            ->and_where_open()
                ->where('u.allowed', '=', 1)
                ->and_where('u.signed', '=', false)
            ->and_where_close()
            ->or_where_open()
                ->where('u.username', '=', 'test')
                ->where('u.color', '=', 'blue')
            ->or_where_close()
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE (u.age > 18 OR u.adult = '1') AND (u.allowed = 1 AND u.signed = '0') OR (u.username = 'test' AND u.color = 'blue')";

        $this->assertSame($expectedQuery, $query);
    }

    public function testWhereClosingEmptyQuery()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->where_open()
                ->where('u.age', '>', 18)
                ->or_where('u.adult', '=', 1)
            ->where_close_empty()
            ->and_where_open()
            ->where_close_empty()
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE (u.age > 18 OR u.adult = 1)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testWhereWithBetweenOperator()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->where('u.age', 'BETWEEN', array(6, 18))
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE u.age BETWEEN 6 AND 18";

        $this->assertSame($expectedQuery, $query);
    }

    public function testWhereWithNullCondition()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->where('u.age', '=', null)
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE u.age IS NULL";

        $this->assertSame($expectedQuery, $query);
    }

    public function testWhereWithNotNullCondition()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->where('u.age', '!=', null)
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE u.age IS NOT NULL";

        $this->assertSame($expectedQuery, $query);
    }
}
 