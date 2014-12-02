<?php

class SelectTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Quark\Query\Select
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
        $this->queryBuilder      = new \Quark\Query\Select(array(
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
            ->havingOpen()
                ->having('u.age', '>', '10')
                ->orHaving('u.age', '<', '14')
            ->havingClose()
            ->orderBy('u.age', 'DESC')
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
            ->havingOpen()
                ->having('u.age', '>', '10')
                ->orHaving('u.age', '<', '14')
            ->havingClose()
            ->orderBy('u.age', 'DESC')
            ->limit(10)
            ->reset()
            ->compile();

        $afterReset = "SELECT *";

        $this->assertSame($afterReset, $query);
    }

    public function testGroupByQuery()
    {
        $qb = new \Quark\Query\Select(array(
            array('u.id', 'id'),
            array('u.username', 'name'),
            array(new \Quark\Statement\Expression('COUNT(u.id)'), 'amount')
        ));

        $query = $qb
            ->from(array('users', 'u'))
            ->groupBy('u.active', 'u.blocked')
            ->compile();

        $expectedQuery = "SELECT u.id AS id, u.username AS name, COUNT(u.id) AS amount FROM users AS u GROUP BY u.active, u.blocked";

        $this->assertSame($expectedQuery, $query);
    }

    public function testUnionQuery()
    {
        $union = new \Quark\Query\Select(array(
            array('u.id', 'id'),
            array('u.username', 'name'),
            array(new \Quark\Statement\Expression('COUNT(u.id)'), 'amount')
        ));

        $union
            ->from(array('users', 'u'))
            ->groupBy('u.active');

        $select = new \Quark\Query\Select(array(
            array('u.id', 'id'),
            array('u.username', 'name'),
            array(new \Quark\Statement\Expression('COUNT(u.id)'), 'amount')
        ));

        $query = $select
            ->from(array('users', 'u'))
            ->groupBy('u.blocked')
            ->union($union)
            ->compile();

        $expectedQuery = "(SELECT u.id AS id, u.username AS name, COUNT(u.id) AS amount FROM users AS u GROUP BY u.blocked) UNION ALL (SELECT u.id AS id, u.username AS name, COUNT(u.id) AS amount FROM users AS u GROUP BY u.active)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testExceptionWhenUnionIsNotInstanceOfSelect()
    {
        $delete = new \Quark\Query\Delete('posts');
        $delete
            ->where('posts.id', 'IN', array(1, 2, 3))
            ->orWhereOpen()
                ->where('posts.title', 'LIKE', '%test%')
                ->orWhere('posts.title', 'LIKE', '%qwer%')
            ->orWhereClose();

        $select = new \Quark\Query\Select(array(
            array('u.id', 'id'),
            array('u.username', 'name'),
            array(new \Quark\Statement\Expression('COUNT(u.id)'), 'amount')
        ));

        try {
            $select
                ->from(array('users', 'u'))
                ->groupBy('u.blocked')
                ->union($delete)
                ->compile();

            $this->assertTrue(false, 'Exception not thrown');
        } catch (\Quark\Exception\QuarkException $e) {
            $this->assertTrue(true, 'Exception thrown');
        }
    }

    public function testUnionNotAllAndStringInsteadOfSelectBuilder()
    {
        $select = new \Quark\Query\Select();

        $query = $select
            ->from(array('other_users', 'ou'))
            ->union('users', false)
            ->compile();

        $expectedQuery = "(SELECT * FROM other_users AS ou) UNION (SELECT * FROM users)";

        $this->assertSame($expectedQuery, $query);
    }

    public function testHavingSimpleQuery()
    {
        $qb = new \Quark\Query\Select(array(
            array('users.id', 'id'),
            array('users.username', 'name'),
            array('users.password', 'pass')
        ));

        $query = $qb
            ->from(array('users', 'u'))
            ->having('u.age', '<', 18)
            ->orHavingOpen()
                ->having('u.age', '>=', 18)
                ->andHaving('u.status', '=', 'child')
            ->orHavingClose()
            ->compile();

        $expectedQuery = "SELECT users.id AS id, users.username AS name, users.password AS pass FROM users AS u HAVING u.age < 18 OR (u.age >= 18 AND u.status = 'child')";

        $this->assertSame($expectedQuery, $query);
    }

    public function testHavingComplexQuery()
    {
        $qb = new \Quark\Query\Select(array(
            array('users.id', 'id'),
            array('users.username', 'name'),
            array('users.password', 'pass')
        ));

        $query = $qb
            ->from(array('users', 'u'))
            ->havingOpen()
                ->having('u.age', '<', 18)
                ->orHaving('u.status', '=', 'child')
            ->havingClose()
            ->orHavingOpen()
                ->having('u.age', '>=', 18)
                ->andHaving('u.status', '=', 'child')
            ->orHavingClose()
            ->compile();

        $expectedQuery = "SELECT users.id AS id, users.username AS name, users.password AS pass FROM users AS u HAVING (u.age < 18 OR u.status = 'child') OR (u.age >= 18 AND u.status = 'child')";

        $this->assertSame($expectedQuery, $query);
    }

    public function testSelectUsingMethodForSelectingFields()
    {
        $qb = new \Quark\Query\Select();

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
        $qb = new \Quark\Query\Select();

        $query = $qb
            ->selectArray(array(
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
        $qb = new \Quark\Query\Select();

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
        $qb = new \Quark\Query\Select();

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
        $qb = new \Quark\Query\Select();

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
 