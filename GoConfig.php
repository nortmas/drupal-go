<?php

return [
  'project_name' => 'Drupal Go',
  'project_machine_name' => 'dgo',
  'theme_name' => 'olivero',
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
  'admin_theme' => [
    'gin' => "^3.0", // https://www.drupal.org/project/gin
  ],
  'modules' => [
    /** Administration **/
    'gin_toolbar'                => '^1.0', // https://www.drupal.org/project/gin_toolbar
    'admin_toolbar'              => '^3.0', // https://www.drupal.org/project/admin_toolbar
    'config_split'               => '^2.0', // https://www.drupal.org/project/config_split
    'devel'                      => '^4.0', // https://www.drupal.org/project/devel
    'coffee'                     => '^1.0', // https://www.drupal.org/project/coffee
    'flood_control'              => '^2.0', // https://www.drupal.org/project/flood_control
    'environment_indicator'      => '^4.0', // https://www.drupal.org/project/environment_indicator
    'masquerade'                 => '^2.0', // https://www.drupal.org/project/masquerade
    'config_ignore'              => '^2.0', // https://www.drupal.org/project/config_ignore
    'dblog_filter'               => '^2.0', // https://www.drupal.org/project/dblog_filter
    'watchdog_prune'             => '^2.0', // https://www.drupal.org/project/watchdog_prune
    'stage_file_proxy'           => '^2.0', // https://www.drupal.org/project/stage_file_proxy
    'backup_migrate'             => '^5.0', // https://www.drupal.org/project/backup_migrate
    'backup_migrate_aws_s3'      => '^5.0', // https://www.drupal.org/project/backup_migrate_aws_s3
    'ultimate_cron'              => '^2.0', // https://www.drupal.org/project/ultimate_cron
    'entity_clone'               => '^2.0', // https://www.drupal.org/project/entity_clone
    /** User Interface **/
    'chosen'                     => '^3.0', // https://www.drupal.org/project/chosen
    'allowed_formats'            => '^1.0', // https://www.drupal.org/project/allowed_formats
    'editor_advanced_link'       => '^2.0', // https://www.drupal.org/project/editor_advanced_link
    'field_group'                => '^3.0', // https://www.drupal.org/project/field_group
    'hide_revision_field'        => '^2.0', // https://www.drupal.org/project/hide_revision_field
    'linkit'                     => '^6.0', // https://www.drupal.org/project/linkit
    'maxlength'                  => '^2.0', // https://www.drupal.org/project/maxlength
    'length_indicator'           => '^1.0', // https://www.drupal.org/project/length_indicator
    /** SEO and Security **/
    'seckit'                     => '^2.0', // https://www.drupal.org/project/seckit
    'simple_sitemap'             => '^4.0', // https://www.drupal.org/project/simple_sitemap
    'metatag'                    => '^1.0', // https://www.drupal.org/project/metatag
    'pathauto'                   => '^1.0', // https://www.drupal.org/project/pathauto
    'rabbit_hole'                => '^1.0', // https://www.drupal.org/project/rabbit_hole
    'redirect'                   => '^1.0', // https://www.drupal.org/project/redirect
    /** User Ecosystem **/
    'password_policy'            => '^3.0', // https://www.drupal.org/project/password_policy
    'mail_login'                 => '^2.0', // https://www.drupal.org/project/mail_login
    /** Media Ecosystem **/
    'media_library_edit'         => '^2.0', // https://www.drupal.org/project/media_library_edit
    'media_responsive_thumbnail' => '^1.0', // https://www.drupal.org/project/media_responsive_thumbnail
    'imagemagick'                => '^3.0', // https://www.drupal.org/project/imagemagick
    'lazy'                       => '^3.0', // https://www.drupal.org/project/lazy
    'svg_image_field'            => '^2.0', // https://www.drupal.org/project/svg_image_field
    'focal_point'                => '^1.0', // https://www.drupal.org/project/focal_point
    'webp'                       => '^1.0', // https://www.drupal.org/project/webp
    /** Paragraph Ecosystem **/
    /** More modules here: https://www.drupal.org/project/paragraphs/ecosystem **/
    'paragraphs'                 => '^1.0', // https://www.drupal.org/project/paragraphs
    'paragraphs_browser'         => '^1.0', // https://www.drupal.org/project/paragraphs_browser
    // https://www.drupal.org/project/paragraphs_sets
    // https://www.drupal.org/project/paragraphs_ee
    // https://www.drupal.org/project/paragraphs_features
    /** Performance **/
    'quicklink'                  => '^2.0', // https://www.drupal.org/project/quicklink
  ],
  'submodules_to_enable' => [
    'admin_toolbar_tools',
    'admin_toolbar_links_access_filter',
  ],
];