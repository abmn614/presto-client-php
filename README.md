# Presto Client PHP

A [Presto](https://prestodb.io) client for the [PHP](http://www.php.net/) programming language.

> Inspired by [illuminate/database](https://github.com/illuminate/database)

## Features

- Multiple connections define.
- Get result as an associative array.
- add support for session
- add support for insert overwrite

## Installation

```
composer require abmn614/presto-client-php
```

## Quick Start

Create a presto manager
```php
<?php

use Clouding\Presto\Presto;

$presto = new Presto();

$presto->addConnection([
    'host' => 'localhost:8080',
    'catalog' => 'default',
    'schema' => 'presto',
]);

// Set manager as global (optional)
$presto->setAsGlobal();
```

Get a default connection and send query
```php
<?php

$posts = $presto->connection()->query('select * from posts')->get();
```

If set manager as global, just query directly and get data
```php
<?php

$posts = Presto::query('SELECT * FROM posts')->get();
/* 
    [
        [1, 'Good pracetice'],
        [2, 'Make code cleaner'],
    ]
*/

$posts = Presto::query('SELECT * FROM posts')->getAssoc();
/* 
    [
        ['id' => 1, 'title' => 'Good pracetice'],
        ['id' => 2, 'title' => 'Make code cleaner'],
    ]
*/    
```

## Usage

### Multiple connections

```php
<?php

use Clouding\Presto\Presto;

$presto = new Presto();

$presto->addConnection([
    'host' => 'localhost:8080',
    'catalog' => 'default',
    'schema' => 'presto',
]);

$presto->addConnection([
    'host' => 'localhost:8080',
    'catalog' => 'default2',
    'schema' => 'presto2',
], 'presto2');

$presto->setAsGlobal();

// Get connections
$connections = Presto::getConnections();

// Specify connection
$posts = Presto::query('SELECT * FROM posts', 'presto2')->get();
```

## Running Tests
```
composer test
```
