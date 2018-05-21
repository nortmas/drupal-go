<?php

return [
  'project_name' => 'Drupal',
  'project_machine_name' => 'drupal',
  'include_module_list' => 0,
  'drush' => [
    'sql' => [
      'tables' => [
        'structure' => [
          'cache',
          'cache_*',
          'history',
          'search_*',
          'sessions',
          'watchdog',
        ],
        'skip' => [
          'migration_*',
        ],
      ],
    ],
    'aliases' => [
      'dev' => [
        'host' => '',
        'user' => '',
        'root' => '',
        'uri' => '',
      ],
      'stage' => [
        'host' => '',
        'user' => '',
        'root' => '',
        'uri' => '',
      ],
      'prod' => [
        'host' => '',
        'user' => '',
        'root' => '',
        'uri' => '',
      ],
    ]
  ],
  'multisite' => [
    # Should be in a format 'alias' => 'real production domain'
    #'subdomain' => 'subdomain.com',
  ],
  'php' => [
    'xdebug' => 1,
  ],
  'webserver' => [
    'type' => 'apache',
  ],
  'varnish' => [
    'enable' => 0,
  ],
  'dbbrowser' => [
    'type' => 'adminer',
  ],
  'solr' => [
    'enable' => 0,
  ],
  'redis' => [
    'enable' => 0,
  ],
  'node' => [
    'enable' => 0,
    'key' => '',
    'path' => '',
  ],
  'memcached' => [
    'enable' => 0,
  ],
  'rsyslog' => [
    'enable' => 0,
  ],
  'athenapdf' => [
    'enable' => 0,
    'key' => '',
  ],
  'blackfire' => [
    'enable' => 0,
    'id' => '',
    'token' => '',
  ],
  'webgrind' => [
    'enable' => 0,
  ],
];