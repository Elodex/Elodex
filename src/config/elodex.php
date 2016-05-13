<?php

return [
    /*
      |--------------------------------------------------------------------------
      | Elasticsearch Client Configuration
      |--------------------------------------------------------------------------
      |
      | These options will be passed to the Elasticsearch client instance.
      |
      | For more info about configuration options visit:
      | http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_configuration.html
      |
    */
    'config' => [
        'hosts'     => ['localhost:9200'],
        'logPath'   => storage_path('logs/elasticsearch.log'),
        'logLevel'  => \Monolog\Logger::INFO,
        'retries'   => 1,
    ],

    /*
      |--------------------------------------------------------------------------
      | Default Index Name
      |--------------------------------------------------------------------------
      |
      | The default index name used by all indexed models.
      |
    */
    'default_index' => 'laravel_elodex_index',

    /*
      |--------------------------------------------------------------------------
      | Index Analyzers
      |--------------------------------------------------------------------------
      |
      | Analyzers added to the ElasticSearch index during index creation with
      | the 'es:create-index' command.
      |
     */
    'analyzer' => [
    ],

];
