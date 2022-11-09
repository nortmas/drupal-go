<?php

return [
  'project_name' => 'Drupal Go',
  'project_machine_name' => 'dgo',
  'include_basic_modules' => 0,
  'theme_name' => 'bartik',
  'servers' => [
    'dev' => [
      'domain' => '178.128.83.195.xip.io',
      'project_dir' => '~/drupal_go',
      'host' => '178.128.83.195',
      'user' => 'deploy',
      'branch' => 'dev',
    ],
    'stage' => [
      'domain' => '178.128.83.195.xip.io',
      'project_dir' => '~/drupal_go',
      'host' => '178.128.83.195',
      'user' => 'deploy',
      'branch' => 'stage',
    ],
    'prod' => [
      'domain' => '178.128.83.195.xip.io',
      'project_dir' => '~/drupal_go',
      'host' => '178.128.83.195',
      'user' => 'deploy',
      'branch' => 'master',
    ],
  ],
  'deploy' => [
    'enable' => 1,
    'runner_artifact_dir' => '/builds/artifact',
  ],
  'multisite' => [
    # Should be in a format 'alias' => 'real production domain'
    # Make sure that one of the domain aliases equals the project_machine_name.
    #'subdomain' => 'subdomain.com',
  ],
  'behat' => [
    'enable' => 0,
    'selenium_tag' => '3.141.59-oxygen',
  ],
  'mariadb' => [
    'tag' => '10.8-3.21.7',
  ],
  'php' => [
    'tag' => '8.1-dev-${OS}4.38.2',
  ],
  'crontab' => [
    'enable' => 0,
  ],
  'webserver' => [
    'type' => 'apache',
    'nginx_tag' => '1.23-5.25.7',
    'apache_tag' => '2.4-4.10.3',
  ],
  'emulsify' => [
    'enable' => 0,
  ],
  'node' => [
    'enable' => 0,
  ],
  'mailhog' => [
    'enable' => 0,
  ],
  'varnish' => [
    'enable' => 0,
    'tag' => '6.0-4.11.3',
  ],
  'dbbrowser' => [
    'enable' => 0,
    'type' => 'adminer',
    'adminer_tag' => '4-3.24.2',
  ],
  'solr' => [
    'enable' => 0,
    'tag' => '8-4.18.1',
  ],
  'redis' => [
    'enable' => 0,
    'tag' => '7-3.14.4',
  ],
  'memcached' => [
    'enable' => 0,
    'tag' => '1-2.13.4',
  ],
  'rsyslog' => [
    'enable' => 0,
    'tag' => 'latest',
  ],
  'athenapdf' => [
    'enable' => 0,
    'key' => '',
    'tag' => '2.16.0',
  ],
  'webgrind' => [
    'enable' => 0,
    'tag' => '1-1.29.2',
  ],
  'blackfire' => [
    'enable' => 0,
    'id' => '',
    'token' => '',
  ],
];