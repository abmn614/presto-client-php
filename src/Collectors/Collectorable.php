<?php

declare(strict_types=1);

namespace Clouding\Presto\Collectors;

interface Collectorable
{
    /**
     * Collect needs data from presto response.
     *
     * @param $response
     */
    public function collect($response);

    /**
     * Get collect data.
     *
     * @return array
     */
    public function get(): array;
}
