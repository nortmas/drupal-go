<?php

return [
  'project_name' => 'Drupal Go',
  'project_machine_name' => 'dgo',
  'include_basic_modules' => 0,
  'port' => 8000,
  'server' => [
    'domain' => '178.128.83.195.xip.io',
    'project_dir' => 'drupal_go',
    'host' => '178.128.83.195',
    'user' => 'deploy',
  ],
  'deploy' => [
    'enable' => 0,
    'runner_artifact_dir' => '/home/gitlab-runner/artifacts',
  ],
  'multisite' => [
    # Should be in a format 'alias' => 'real production domain'
    #'subdomain' => 'subdomain.com',
  ],
  'behat' => [
    'enable' => 0,
  ],
  'mariadb' => [
    'tag' => '10.1-3.3.11',
  ],
  'php' => [
    'xdebug' => 1,
    'tag' => '7.2-dev-${OS}4.9.2',
  ],
  'webserver' => [
    'type' => 'apache',
    'nginx_tag' => '1.15-5.0.17',
    'apache_tag' => '2.4-4.0.2',
  ],
  'mailhog' => [
    'enable' => 0,
  ],
  'varnish' => [
    'enable' => 0,
    'tag' => '4.1-3.0.10',
  ],
  'dbbrowser' => [
    'enable' => 0,
    'type' => 'adminer',
    'adminer_tag' => '4.6-3.1.2',
  ],
  'solr' => [
    'enable' => 0,
    'tag' => '7.4-3.0.6',
  ],
  'redis' => [
    'enable' => 0,
    'tag' => '4-3.0.1',
  ],
  'node' => [
    'enable' => 0,
    'key' => '',
    'path' => '',
    'tag' => '1.0-2.0.0',
  ],
  'memcached' => [
    'enable' => 0,
    'tag' => '1-2.2.1',
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
    'tag' => '1.5-1.6.2',
  ],
  'blackfire' => [
    'enable' => 0,
    'id' => '',
    'token' => '',
  ],
];