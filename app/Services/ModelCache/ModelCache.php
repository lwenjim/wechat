<?php

namespace App\Services\ModelCache;

trait ModelCache
{
    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();
        $grammar = $conn->getQueryGrammar();
        $builder = new Builder($conn, $grammar, $conn->getPostProcessor());
        if (isset($this->cacheTag)) {
            $builder->cacheTags($this->cacheTag);
        }
        if (isset($this->cachePrefix)) {
            $builder->cachePrefix($this->cachePrefix);
        }
        if (isset($this->cacheDriver)) {
            $builder->cacheDriver($this->cacheDriver);
        }
        return $builder;
    }
}