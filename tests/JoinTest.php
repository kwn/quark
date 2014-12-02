<?php

class JoinTest extends PHPUnit_Framework_TestCase
{
    public function testExceptionWhenMixingJoinOnAndUsing()
    {
        $qb = new \Quark\Query\Select();

        try {
            $qb
                ->select('u.id', 'u.username', 'pass')
                ->from('users', 'u')
                ->join(array('posts', 'p'))
                    ->on('p.user_id', '=', 'u.id')
                    ->using('user_id')
                ->compile();

            $this->assertTrue(false, 'Exception not thrown');
        } catch (\Quark\Exception\QuarkException $e) {
            $this->assertTrue(true, 'Exception thrown');
        }
    }

    public function testExceptionWhenMixingUsingAndOn()
    {
        $qb = new \Quark\Query\Select();

        try {
            $qb
                ->select('u.user_id', 'u.username', 'pass')
                ->from('users', 'u')
                ->join(array('posts', 'p'))
                    ->using('user_id')
                    ->on('p.user_id', '=', 'u.user_id')
                ->compile();

            $this->assertTrue(false, 'Exception not thrown');
        } catch (\Quark\Exception\QuarkException $e) {
            $this->assertTrue(true, 'Exception thrown');
        }
    }

    public function testStandaloneJoinStatementWithOn()
    {
        $qb = new \Quark\Statement\Join(array('posts', 'p'));

        $query = $qb
            ->on('p.user_id', '=', 'u.id')
            ->compile();

        $expectedQuery = "JOIN posts AS p ON (p.user_id = u.id)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testStandaloneJoinStatementWithUsing()
    {
        $qb = new \Quark\Statement\Join(array('posts', 'p'));

        $query = $qb
            ->using('user_id')
            ->compile();

        $expectedQuery = "JOIN posts AS p USING (user_id)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testStandaloneJoinReset()
    {
        $qb = new \Quark\Statement\Join(array('posts', 'p'));

        $qb->on('p.user_id', '=', 'u.id');
        $qb->reset();

        $query = $qb->compile();

        $expectedQuery = "JOIN  ON ()";

        $this->assertSame($expectedQuery, $query);
    }
}
 