<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait WithCaching
{
    protected $useCachedRows = false;

    public function useCachedRows($useCache = true): void
    {
        $this->useCachedRows = $useCache;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function cache($callback)
    {
        $cacheKey = $this->id;

        if ($this->useCachedRows && cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        $results = $callback();

        cache()->put($cacheKey, $results);

        return $results;
    }
}
