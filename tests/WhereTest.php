<?php

class WhereTest extends PHPUnit_Framework_TestCase
{
    public function testWhereSimpleQuery()
    {
        $qb = new \Quark\Query\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->whereOpen()
                ->where('u.age', '>', 18)
                ->orWhere('u.adult', '=', true)
            ->whereClose()
            ->andWhereOpen()
                ->where('u.allowed', '=', 1)
                ->andWhere('u.signed', '=', false)
            ->andWhereClose()
            ->orWhereOpen()
                ->where('u.username', '=', 'test')
                ->where('u.color', '=', 'blue')
            ->orWhereClose()
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE (u.age > 18 OR u.adult = '1') AND (u.allowed = 1 AND u.signed = '0') OR (u.username = 'test' AND u.color = 'blue')";

        $this->assertSame($expectedQuery, $query);
    }

    public function testWhereClosingEmptyQuery()
    {
        $qb = new \Quark\Query\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->whereOpen()
                ->where('u.age', '>', 18)
                ->orWhere('u.adult', '=', 1)
            ->whereCloseEmpty()
            ->andWhereOpen()
            ->whereCloseEmpty()
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE (u.age > 18 OR u.adult = 1)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testWhereWithBetweenOperator()
    {
        $qb = new \Quark\Query\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->where('u.age', 'BETWEEN', array(6.5, 18))
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE u.age BETWEEN 6.500000 AND 18";

        $this->assertSame($expectedQuery, $query);
    }

    public function testWhereWithNullCondition()
    {
        $qb = new \Quark\Query\Select();

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
        $qb = new \Quark\Query\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->where('u.age', '!=', null)
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u WHERE u.age IS NOT NULL";

        $this->assertSame($expectedQuery, $query);
    }
}
 