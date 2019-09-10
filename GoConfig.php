<?php

return [
  'project_name' => 'Drupal Go',
  'project_machine_name' => 'dgo',
  'include_basic_modules' => 1,
  'theme_name' => 'bartik',
  'servers' => [
    'dev' => [
      'domain' => '178.128.83.195.xip.io',
      'project_dir' => '~/drupal_go',
      'host' => '178.128.83.195',
      'user' => 'deploy',
      'branch' => 'dev'
    ],
    'stage' => [
      'domain' => '178.128.83.195.xip.io',
      'project_dir' => '~/drupal_go',
      'host' => '178.128.83.195',
      'user' => 'deploy',
      'branch' => 'stage'
    ],
    'prod' => [
      'domain' => '178.128.83.195.xip.io',
      'project_dir' => '~/drupal_go',
      'host' => '178.128.83.195',
      'user' => 'deploy',
      'branch' => 'master'
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
    'tag' => '10.3-3.6.3',
  ],
  'php' => [
    'tag' => '7.3-dev-${OS}4.13.10',
  ],
  'webserver' => [
    'type' => 'apache',
    'nginx_tag' => '1.17-5.6.7',
    'apache_tag' => '2.4-4.1.3',
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
    'tag' => '6.0-4.3.4',
  ],
  'dbbrowser' => [
    'enable' => 0,
    'type' => 'adminer',
    'adminer_tag' => '4-3.6.10',
  ],
  'solr' => [
    'enable' => 0,
    'tag' => '8-4.0.3',
  ],
  'redis' => [
    'enable' => 0,
    'tag' => '4-3.0.1',
  ],
  'memcached' => [
    'enable' => 1,
    'tag' => '1-2.3.3',
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
    'tag' => '1.5-1.11.8',
  ],
  'blackfire' => [
    'enable' => 0,
    'id' => '',
    'token' => '',
  ],
];