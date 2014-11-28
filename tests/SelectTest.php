<?php

class SelectTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Quark\Database\Query\Builder\Select
     */
    public $queryBuilder;

    /**
     * @var string
     */
    public $simpleResultQuery;

    public function setUp()
    {
        parent::setUp();

        $this->simpleResultQuery = "SELECT DISTINCT users.id AS id, users.username AS name, users.password AS pass FROM users AS u LEFT JOIN posts AS p ON (p.user_id = u.id) RIGHT JOIN venues AS v ON (v.user_id = u.id) WHERE u.name = 'test' HAVING (u.age > '10' OR u.age < '14') ORDER BY u.age DESC LIMIT 10";
        $this->queryBuilder      = new \Quark\Database\Query\Builder\Select(array(
            array('users.id', 'id'),
            array('users.username', 'name'),
            array('users.password', 'pass')
        ));
    }

    public function testSimpleQuery()
    {
        $query = $this
            ->queryBuilder
            ->distinct(true)
            ->from(array('users', 'u'))
            ->join(array('posts', 'p'), 'LEFT')
                ->on('p.user_id', '=', 'u.id')
            ->join(array('venues', 'v'), 'RIGHT')
                ->on('v.user_id', '=', 'u.id')
            ->where('u.name', '=', 'test')
            ->having_open()
                ->having('u.age', '>', '10')
                ->or_having('u.age', '<', '14')
            ->having_close()
            ->order_by('u.age', 'DESC')
            ->limit(10)
            ->compile();

        $this->assertSame($this->simpleResultQuery, $query);
    }

    public function testResetQuery()
    {
        $query = $this
            ->queryBuilder
            ->distinct(true)
            ->from(array('users', 'u'))
            ->join(array('posts', 'p'), 'LEFT')
                ->on('p.user_id', '=', 'u.id')
            ->join(array('venues', 'v'), 'RIGHT')
                ->on('v.user_id', '=', 'u.id')
            ->where('u.name', '=', 'test')
            ->having_open()
                ->having('u.age', '>', '10')
                ->or_having('u.age', '<', '14')
            ->having_close()
            ->order_by('u.age', 'DESC')
            ->limit(10)
            ->reset()
            ->compile();

        $afterReset = "SELECT *";

        $this->assertSame($afterReset, $query);
    }

    public function testGroupByQuery()
    {
        $qb = new Quark\Database\Query\Builder\Select(array(
            array('u.id', 'id'),
            array('u.username', 'name'),
            array('COUNT(u.id)', 'amount')
        ));

        $query = $qb
            ->from(array('users', 'u'))
            ->group_by('u.active', 'u.blocked')
            ->compile();

        $expectedQuery = "SELECT u.id AS id, u.username AS name, COUNT(u.id) AS amount FROM users AS u GROUP BY u.active, u.blocked";

        $this->assertSame($expectedQuery, $query);
    }

    public function testUnionQuery()
    {
        $union = new Quark\Database\Query\Builder\Select(array(
            array('u.id', 'id'),
            array('u.username', 'name'),
            array('COUNT(u.id)', 'amount')
        ));

        $union
            ->from(array('users', 'u'))
            ->group_by('u.active');

        $select = new Quark\Database\Query\Builder\Select(array(
            array('u.id', 'id'),
            array('u.username', 'name'),
            array('COUNT(u.id)', 'amount')
        ));

        $query = $select
            ->from(array('users', 'u'))
            ->group_by('u.blocked')
            ->union($union)
            ->compile();

        $expectedQuery = "(SELECT u.id AS id, u.username AS name, COUNT(u.id) AS amount FROM users AS u GROUP BY u.blocked) UNION ALL (SELECT u.id AS id, u.username AS name, COUNT(u.id) AS amount FROM users AS u GROUP BY u.active)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testExceptionWhenUnionIsNotInstanceOfSelect()
    {
        $delete = new \Quark\Database\Query\Builder\Delete('posts');
        $delete
            ->where('posts.id', 'IN', array(1, 2, 3))
            ->or_where_open()
                ->where('posts.title', 'LIKE', '%test%')
                ->or_where('posts.title', 'LIKE', '%qwer%')
            ->or_where_close();

        $select = new Quark\Database\Query\Builder\Select(array(
            array('u.id', 'id'),
            array('u.username', 'name'),
            array('COUNT(u.id)', 'amount')
        ));

        try {
            $select
                ->from(array('users', 'u'))
                ->group_by('u.blocked')
                ->union($delete)
                ->compile();

            $this->assertTrue(false, 'Exception not thrown');
        } catch (\Quark\Exception\QuarkException $e) {
            $this->assertTrue(true, 'Exception thrown');
        }
    }

    public function testUnionNotAllAndStringInsteadOfSelectBuilder()
    {
        $select = new Quark\Database\Query\Builder\Select();

        $query = $select
            ->from(array('other_users', 'ou'))
            ->union('users', false)
            ->compile();

        $expectedQuery = "(SELECT * FROM other_users AS ou) UNION (SELECT * FROM users)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testHavingSimpleQuery()
    {
        $qb = new \Quark\Database\Query\Builder\Select(array(
            array('users.id', 'id'),
            array('users.username', 'name'),
            array('users.password', 'pass')
        ));

        $query = $qb
            ->from(array('users', 'u'))
            ->having('u.age', '<', 18)
            ->or_having_open()
                ->having('u.age', '>=', 18)
                ->and_having('u.status', '=', 'child')
            ->or_having_close()
            ->compile();

        $expectedQuery = "SELECT users.id AS id, users.username AS name, users.password AS pass FROM users AS u HAVING u.age < 18 OR (u.age >= 18 AND u.status = 'child')";

        $this->assertSame($expectedQuery, $query);
    }

    public function testHavingComplexQuery()
    {
        $qb = new \Quark\Database\Query\Builder\Select(array(
            array('users.id', 'id'),
            array('users.username', 'name'),
            array('users.password', 'pass')
        ));

        $query = $qb
            ->from(array('users', 'u'))
            ->having_open()
                ->having('u.age', '<', 18)
                ->or_having('u.status', '=', 'child')
            ->having_close()
            ->or_having_open()
                ->having('u.age', '>=', 18)
                ->and_having('u.status', '=', 'child')
            ->or_having_close()
            ->compile();

        $expectedQuery = "SELECT users.id AS id, users.username AS name, users.password AS pass FROM users AS u HAVING (u.age < 18 OR u.status = 'child') OR (u.age >= 18 AND u.status = 'child')";

        $this->assertSame($expectedQuery, $query);
    }

    public function testSelectUsingMethodForSelectingFields()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select(
                array('users.id', 'id'),
                array('users.username', 'name'),
                array('users.password', 'pass')
            )
            ->from(array('users', 'u'))
            ->compile();

        $expectedQuery = "SELECT users.id AS id, users.username AS name, users.password AS pass FROM users AS u";

        $this->assertSame($expectedQuery, $query);
    }

    public function testSelectUsingMethodForSelectingFieldsUsingArray()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select_array(array(
                array('users.id', 'id'),
                array('users.username', 'name'),
                array('users.password', 'pass')
            ))
            ->from(array('users', 'u'))
            ->compile();

        $expectedQuery = "SELECT users.id AS id, users.username AS name, users.password AS pass FROM users AS u";

        $this->assertSame($expectedQuery, $query);
    }

    public function testJoinWithUsing()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select(
                array('users.id', 'id'),
                array('users.username', 'uname'),
                array('users.password', 'pass')
            )
            ->from(array('users', 'u'))
            ->join(array('posts', 'p'))
            ->using('post_id')
            ->compile();

        $expectedQuery = "SELECT users.id AS id, users.username AS uname, users.password AS pass FROM users AS u JOIN posts AS p USING (post_id)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testQueryWithLimitAndOffset()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->limit(10)
            ->offset(10)
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u LIMIT 10 OFFSET 10";

        $this->assertSame($expectedQuery, $query);
    }

    public function testQueryWithMultipleOrderBy()
    {
        $qb = new \Quark\Database\Query\Builder\Select();

        $query = $qb
            ->select('id', 'username', 'pass')
            ->from('users', 'u')
            ->limit(10)
            ->offset(10)
            ->compile();

        $expectedQuery = "SELECT id, username, pass FROM users, u LIMIT 10 OFFSET 10";

        $this->assertSame($expectedQuery, $query);
    }
}
 