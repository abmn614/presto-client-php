<?php

declare(strict_types=1);

namespace Clouding\Presto\Collectors;

class AssocCollector implements Collectorable
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
        if (!isset($response->data, $response->columns)) {
            return;
        }

        $this->collection = array_merge($this->collection, $this->getAssocData($response));
    }

    /**
     * Get associated data.
     *
     * @param $response
     * @return array
     */
    protected function getAssocData($response): array
    {
        $columns = array_column($response->columns, 'name');

        return array_map(function (array $data) use ($columns) {
            return array_combine($columns, $data);
        }, $response->data);
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
