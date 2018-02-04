<?php

namespace Suitmedia\Cacheable\Contracts;

interface CacheableModel
{
    /**
     * Return the cache duration value
     * for this model.
     *
     * @return integer
     */
    public function cacheDuration();

    /**
     * Return the cache tags which would be used
     * by the model and model observer.
     *
     * @return mixed
     */
    public function cacheTags();
}
