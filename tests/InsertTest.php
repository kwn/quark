<?php

class InsertTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Quark\Query\Insert
     */
    public $queryBuilder;

    /**
     * @var string
     */
    public $simpleResultQuery;

    public function setUp()
    {
        parent::setUp();

        $this->simpleResultQuery = "INSERT INTO posts (posts.username, posts.email, posts.age) VALUES ('test1', 'test1@test.com', '13'), ('test2', 'test2@test.com', '23'), ('test3', 'test3@test.com', '33')";
        $this->queryBuilder      = new \Quark\Query\Insert();
    }

    public function testSimple()
    {
        $query = $this
            ->queryBuilder
            ->table('posts')
            ->columns(array('posts.username', 'posts.email', 'posts.age'))
            ->values(
                array('test1', 'test1@test.com', '13'),
                array('test2', 'test2@test.com', '23'),
                array('test3', 'test3@test.com', '33')
            )
            ->compile();

        $this->assertSame($this->simpleResultQuery, $query);
    }

    public function testSimpleMultipleValues()
    {
        $query = $this
            ->queryBuilder
            ->table('posts')
            ->columns(array('posts.username', 'posts.email', 'posts.age'))
            ->values(array('test1', 'test1@test.com', '13'))
            ->values(array('test2', 'test2@test.com', '23'))
            ->values(array('test3', 'test3@test.com', '33'))
            ->compile();

        $this->assertSame($this->simpleResultQuery, $query);
    }

    public function testResetQuery()
    {
        $query = $this
            ->queryBuilder
            ->table('posts')
            ->columns(array('posts.username', 'posts.email', 'posts.age'))
            ->values(array('test1', 'test1@test.com', '13'))
            ->reset()
            ->compile();

        $afterReset = "INSERT INTO  () VALUES ";

        $this->assertSame($afterReset, $query);
    }

    public function testInitTableAndColumnsInConstructor()
    {
        $queryBuilder = new \Quark\Query\Insert(
            'posts',
            array('posts.username', 'posts.email', 'posts.age')
        );

        $query = $queryBuilder
            ->values(
                array('test1', 'test1@test.com', '13'),
                array('test2', 'test2@test.com', '23'),
                array('test3', 'test3@test.com', '33')
            )
            ->compile();

        $this->assertSame($this->simpleResultQuery, $query);
    }

    public function testInsertWithSelectSubquery()
    {
        $qb = new \Quark\Query\Select(array(
            'name',
            'email'
        ));

        $select = $qb
            ->from(array('users', 'u'))
            ->join(array('posts', 'p'), 'LEFT')
            ->on('p.user_id', '=', 'u.id')
                ->where('u.name', '=', 'test')
            ->havingOpen()
                ->having('u.age', '>', '10')
                ->orHaving('u.age', '<', '14')
            ->havingClose()
            ->orderBy('u.age', 'DESC');

        $query = $this
            ->queryBuilder
            ->table('posts')
            ->columns(array('posts.username', 'posts.posts', 'posts.age'))
            ->select($select)
            ->compile();

        $expected = "INSERT INTO posts (posts.username, posts.posts, posts.age) SELECT name, email FROM users AS u LEFT JOIN posts AS p ON (p.user_id = u.id) WHERE u.name = 'test' HAVING (u.age > '10' OR u.age < '14') ORDER BY u.age DESC";

        $this->assertSame($expected, $query);
    }

    public function testExceptionWhenTryingToMixValuesArrayAndSelectSubquery()
    {
        $qb = new \Quark\Query\Select(array(
            'name',
            'email'
        ));

        $select = $qb
            ->from(array('users', 'u'))
            ->join(array('posts', 'p'), 'LEFT')
            ->on('p.user_id', '=', 'u.id')
            ->where('u.name', '=', 'test')
            ->havingOpen()
                ->having('u.age', '>', '10')
                ->orHaving('u.age', '<', '14')
            ->havingClose()
            ->orderBy('u.age', 'DESC');

        try {
            $this
                ->queryBuilder
                ->table('posts')
                ->columns(array('posts.username', 'posts.posts', 'posts.age'))
                ->select($select)
                ->values(array('posts.username'));

            $this->assertTrue(false, 'Exception not thrown');
        } catch (\Quark\Exception\QuarkException $e) {
            $this->assertTrue(true, 'Exception thrown');
        }
    }

    public function testExceptionWhenTryingToUseSelectWithDifferentBuilderType()
    {
        $qb = new \Quark\Query\Insert('posts');

        try {
            $this
                ->queryBuilder
                ->table('posts')
                ->columns(array('posts.username', 'posts.posts', 'posts.age'))
                ->select($qb)
                ->values(array('posts.username'));

            $this->assertTrue(false, 'Exception not thrown');
        } catch(\Quark\Exception\QuarkException $e) {
            $this->assertTrue(true, 'Exception thrown');
        }
    }


    public function testExceptionWhenUsingTableAliasInInsertSelectConstruction()
    {
        $qb = new \Quark\Query\Select(array(
            'name',
            'email'
        ));

        $select = $qb
            ->from(array('users', 'u'))
            ->join(array('posts', 'p'), 'LEFT')
            ->on('p.user_id', '=', 'u.id')
            ->where('u.name', '=', 'test')
            ->havingOpen()
                ->having('u.age', '>', '10')
                ->orHaving('u.age', '<', '14')
            ->havingClose()
            ->orderBy('u.age', 'DESC');

        try {
            $this
                ->queryBuilder
                ->table(array('posts', 'p'))
                ->columns(array('p.username', 'p.posts', 'p.age'))
                ->select($select)
                ->compile();

            $this->assertTrue(false, 'Exception not thrown');
        } catch (\Quark\Exception\QuarkException $e) {
            $this->assertTrue(true, 'Exception thrown');
        }
    }
} 