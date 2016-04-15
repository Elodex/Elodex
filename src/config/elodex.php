<?php

return [
    /*
      |--------------------------------------------------------------------------
      | Custom Elasticsearch Client Configuration
      |--------------------------------------------------------------------------
      |
      | This array will be passed to the Elasticsearch client instance.
      |
      | More more info about configuration options visit:
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
      | This is the default index name Elodex will use for all indexed models.
      |
    */
    'default_index' => 'my_custom_index_name',

];
