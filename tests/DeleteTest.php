<?php

class DeleteTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Quark\Query\Delete
     */
    public $queryBuilder;

    /**
     * @var string
     */
    public $simpleResultQuery;

    /**
     * @var string
     */
    public $resultWithLimitAndOrder;

    public function setUp()
    {
        parent::setUp();

        $this->simpleResultQuery       = "DELETE FROM posts WHERE posts.id IN ('1', '2', '3') OR (posts.title LIKE '%test%' OR posts.title LIKE '%qwer%')";
        $this->resultWithLimitAndOrder = "DELETE FROM posts WHERE posts.id IN ('1', '2', '3') OR (posts.title LIKE '%test%' OR posts.title LIKE '%qwer%') ORDER BY posts.views ASC LIMIT 5";
        $this->queryBuilder            = new \Quark\Query\Delete('posts');
    }

    public function testSimpleQuery()
    {
        $query = $this
            ->queryBuilder
            ->where('posts.id', 'IN', array('1', '2', '3'))
            ->orWhereOpen()
                ->where('posts.title', 'LIKE', '%test%')
                ->orWhere('posts.title', 'LIKE', '%qwer%')
            ->orWhereClose()
            ->compile();

        $this->assertSame($this->simpleResultQuery, $query);
    }

    public function testQueryWithLimitAndOrder()
    {
        $query = $this
            ->queryBuilder
            ->where('posts.id', 'IN', array('1', '2', '3'))
            ->orWhereOpen()
                ->where('posts.title', 'LIKE', '%test%')
                ->orWhere('posts.title', 'LIKE', '%qwer%')
            ->orWhereClose()
            ->orderBy('posts.views', 'ASC')
            ->limit(5)
            ->compile();

        $this->assertSame($this->resultWithLimitAndOrder, $query);
    }

    public function testResetQuery()
    {
        $query = $this
            ->queryBuilder
            ->where('posts.id', 'IN', array('1', '2', '3'))
            ->orWhereOpen()
                ->where('posts.title', 'LIKE', '%test%')
                ->orWhere('posts.title', 'LIKE', '%qwer%')
            ->orWhereClose()
            ->reset()
            ->compile();

        $afterReset = "DELETE FROM ";

        $this->assertSame($afterReset, $query);
    }

    public function testExceptionWhenUsingAliasForTable()
    {
        try {
            $this
                ->queryBuilder
                ->table(array('posts', 'p'))
                ->where('p.id', '=', '1');

            $this->assertTrue(false, 'Exception not thrown');
        } catch (\Quark\Exception\QuarkException $e) {
            $this->assertTrue(true, 'Exception thrown');
        }
    }
}
 