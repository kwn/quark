<?php

namespace Quark\Query;

interface QueryInterface
{
    /**
     * Compile the SQL query and return it.
     *
     * @return  string
     */
    public function compile();

    /**
     * Reset query
     *
     * @return $this
     */
    public function reset();
} 