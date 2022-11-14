<?php

declare(strict_types=1);

namespace Clouding\Presto;

use Clouding\Presto\Collectors\Collectorable;
use Clouding\Presto\Connection\Connection;
use Clouding\Presto\Exceptions\PrestoException;
use GuzzleHttp\Client;

class Processor
{
    /**
     * The statement uri.
     *
     * @var string
     */
    const STATEMENT_URI = '/v1/statement';

    /**
     * Send request delay milliseconds.
     *
     * @var int
     */
    const DELAY = 50;

    /**
     * query ID
     * @var string
     */
    public $queryId;

    /**
     * The connection information.
     *
     * @var \Clouding\Presto\Connection\Connection
     */
    protected $connection;

    /**
     * Http client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Response next uri.
     *
     * @var ?string
     */
    protected $nextUri = null;

    /**
     * Collect response data.
     *
     * @var \Clouding\Presto\Collectors\Collectorable
     */
    protected $collector;

    /**
     * Create a new instance.
     *
     * @param \Clouding\Presto\Connection\Connection $connection
     * @param \GuzzleHttp\Client|null                $client
     */
    public function __construct(Connection $connection, Client $client = null)
    {
        $this->connection = $connection;
        $this->client = $client ?? new Client(['delay' => static::DELAY]);
    }

    /**
     * Execute connection query.
     *
     * @param  string                                   $query
     * @param \Clouding\Presto\Collectors\Collectorable $collector
     * @return array
     */
    public function execute(string $query, Collectorable $collector): array
    {
        $this->collector = $collector;

        $response = $this->sendQuery($query);
        $this->queryId = $response->id;
        $this->resolve($response);

        while ($this->hasNextUri()) {
            $this->resolve($this->sendNextUri());
        }

        return $this->collector->get();
    }

    /**
     * Send query request.
     *
     * @param  string $query
     * @return 
     */
    protected function sendQuery(string $query)
    {
        $baseUri = $this->connection->getHost() . static::STATEMENT_URI;
        $headers = [
            'X-Trino-User' => $this->connection->getUser(),
            'X-Trino-Schema' => $this->connection->getSchema(),
            'X-Trino-Catalog' => $this->connection->getCatalog(),
            'X-Trino-Session' => $this->getSession($query),
            'X-Presto-User' => $this->connection->getUser(),
            'X-Presto-Schema' => $this->connection->getSchema(),
            'X-Presto-Catalog' => $this->connection->getCatalog(),
            'X-Presto-Session' => $this->getSession($query),
        ];

        $response = $this->client->post($baseUri, ['headers' => $headers, 'body' => $this->transformQuery($query)]);

        return json_decode((string) $response->getBody());
    }

    protected function getSession(string $query)
    {
        $matchRst = preg_match('/insert\s+overwrite/i', $query);
        if ($matchRst !== false && $matchRst > 0) {
            return 'hive.insert_existing_partitions_behavior=overwrite';
        }
        return '';
    }

    protected function transformQuery(string $query)
    {
        return preg_replace('/insert\s+overwrite/i', 'INSERT INTO', $query);
    }

    /**
     * Send next query.
     *
     * @return 
     */
    protected function sendNextUri()
    {
        $response = $this->client->get($this->nextUri);

        return json_decode((string) $response->getBody());
    }

    /**
     * Resolve response.
     *
     * @param $response
     */
    protected function resolve($response)
    {
        $this->checkState($response);

        $this->setNextUri($response);

        $this->collector->collect($response);
    }

    /**
     * Check response state.
     *
     * @param  $response
     *
     * @throws \Clouding\Presto\Exceptions\PrestoException
     */
    protected function checkState($response)
    {
        if ($response->stats->state === PrestoState::FAILED) {
            $message = "{$response->error->errorName}: {$response->error->message}";
            throw new PrestoException($message);
        }
    }

    /**
     * Set next uri.
     *
     * @param  $response
     */
    protected function setNextUri($response)
    {
        $this->nextUri = $response->nextUri ?? null;
    }

    /**
     * Determine if next uri is set or not.
     *
     * @return bool
     */
    protected function hasNextUri(): bool
    {
        return isset($this->nextUri);
    }
}
