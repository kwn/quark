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
                ->or_where('u.adult', '=', 1)
            ->where_close()
            ->and_where_open()
                ->where('u.allowed', '=', 1)
                ->and_where('u.signed', '=', 1)
            ->and_where_close()
            ->or_where_open()
                ->where('u.username', '=', 'test')
                ->where('u.color', '=', 'blue')
            ->or_where_close()
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE (u.age > 18 OR u.adult = 1) AND (u.allowed = 1 AND u.signed = 1) OR (u.username = 'test' AND u.color = 'blue')";

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
}
 