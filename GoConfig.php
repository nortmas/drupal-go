<?php

return [
  'project_name' => 'Drupal Go',
  'project_machine_name' => 'dgo',
  'include_basic_modules' => 0,
  'port' => 8000,
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
        'user' => 'gitlab-runner',
        'root' => '/var/www/html/web',
        'uri' => '',
        'use_script' => 1
      ],
      'stage' => [
        'host' => '',
        'user' => 'gitlab-runner',
        'root' => '/var/www/html/web',
        'uri' => '',
        'use_script' => 1
      ],
      'prod' => [
        'host' => '',
        'user' => 'gitlab-runner',
        'root' => '/var/www/html/web',
        'uri' => '',
        'use_script' => 1
      ],
    ],
  ],
  'gitlab' => [
    'enable' => 0,
    'staging_domain' => 'staging-domain.com',
    'working_dir' => '/home/gitlab-runner/artifacts',
  ],
  'behat' => [
    'enable' => 0,
    'base_url' => 'http://admin:user2admin@apache:80',
    'region_content' => '.main-content',
    'region_footer' => '.footer',
    'region_navigation' => '#main-nav',
    'region_header' => '.header',
  ],
  'multisite' => [
    # Should be in a format 'alias' => 'real production domain'
    #'subdomain' => 'subdomain.com',
  ],
  'mariadb' => [
    'tag' => '10.1-3.3.9',
  ],
  'php' => [
    'xdebug' => 1,
    'tag' => '7.1-dev-${OS}4.9.0', // The variable $OS is taken from the .env file.
  ],
  'webserver' => [
    'type' => 'apache',
    'nginx_tag' => '1.15-5.0.12',
    'apache_tag' => '2.4-4.0.2',
  ],
  'mailhog' => [
    'enable' => 0,
  ],
  'varnish' => [
    'enable' => 0,
    'tag' => '4.1-2.4.0',
  ],
  'dbbrowser' => [
    'enable' => 0,
    'type' => 'adminer',
    'adminer_tag' => '4.6-3.1.0',
  ],
  'solr' => [
    'enable' => 0,
    'tag' => '6.6-2.4.0',
  ],
  'redis' => [
    'enable' => 0,
    'tag' => '4.0-2.1.5',
  ],
  'node' => [
    'enable' => 0,
    'key' => '',
    'path' => '',
    'tag' => '1.0-2.0.0',
  ],
  'memcached' => [
    'enable' => 0,
    'tag' => '1-2.2.0',
  ],
  'rsyslog' => [
    'enable' => 0,
    'tag' => 'latest',
  ],
  'athenapdf' => [
    'enable' => 0,
    'key' => '',
    'tag' => '2.10.0',
  ],
  'webgrind' => [
    'enable' => 0,
    'tag' => '1.5-1.3.0',
  ],
  'blackfire' => [
    'enable' => 0,
    'id' => '',
    'token' => '',
  ],
];