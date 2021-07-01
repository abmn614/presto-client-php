<?php

declare(strict_types=1);

namespace Clouding\Presto\Collectors;

class BasicCollector implements Collectorable
{
    /**
     * The array of collect data.
     *
     * @var array
     */
    protected $collection = [];

    /**
     * Collect data from presto response.
     *
     * @param $response
     */
    public function collect($response)
    {
        if (!isset($response->data)) {
            return;
        }

        $this->collection = array_merge($this->collection, $response->data);
    }

    /**
     * Get collect data.
     *
     * @return array
     */
    public function get(): array
    {
        return $this->collection;
    }
}
