<?php

class UpdateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Quark\Query\Update
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

        $this->simpleResultQuery       = "UPDATE posts AS p SET p.views = 300, p.active = 1 WHERE posts.id IN (1, 2, 3)";
        $this->resultWithLimitAndOrder = "UPDATE posts AS p SET p.views = 300, p.active = 1 WHERE posts.id IN (1, 2, 3) ORDER BY p.views ASC LIMIT 5";
        $this->queryBuilder            = new \Quark\Query\Update(array('posts', 'p'));
    }

    public function testSimpleQuery()
    {
        $query = $this
            ->queryBuilder
            ->set(array(
                'p.views'  => 300,
                'p.active' => 1
            ))
            ->where('posts.id', 'IN', array(1, 2, 3))
            ->compile();

        $this->assertSame($this->simpleResultQuery, $query);
    }

    public function testSimpleQueryWithOrderByAndLimit()
    {
        $query = $this
            ->queryBuilder
            ->set(array(
                'p.views'  => 300,
                'p.active' => 1
            ))
            ->where('posts.id', 'IN', array(1, 2, 3))
            ->orderBy('p.views', 'ASC')
            ->limit(5)
            ->compile();

        $this->assertSame($this->resultWithLimitAndOrder, $query);
    }

    public function testSimpleQueryWithValueMethod()
    {
        $query = $this
            ->queryBuilder
            ->value('p.views', 300)
            ->value('p.active', 1)
            ->where('posts.id', 'IN', array(1, 2, 3))
            ->compile();

        $this->assertSame($this->simpleResultQuery, $query);
    }

    public function testUpdateSingleValue()
    {
        $query = $this
            ->queryBuilder
            ->value('p.views', 123)
            ->where('posts.id', 'IN', array(1, 2, 3))
            ->compile();

        $queryWithSingleValue = "UPDATE posts AS p SET p.views = 123 WHERE posts.id IN (1, 2, 3)";

        $this->assertSame($queryWithSingleValue, $query);
    }

    public function testResetQuery()
    {
        $query = $this
            ->queryBuilder
            ->set(array(
                'p.views'  => 300,
                'p.active' => 1
            ))
            ->where('posts.id', 'IN', array(1, 2, 3))
            ->reset()
            ->compile();

        $afterReset = "UPDATE  SET ";

        $this->assertSame($afterReset, $query);
    }
}
 