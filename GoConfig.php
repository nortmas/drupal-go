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
    'drupal/gin' => "^3.0", // https://www.drupal.org/project/gin
  ],
  'modules' => [
    'drupal/gin_toolbar' => '^1.0@beta', // https://www.drupal.org/project/gin_toolbar
    'drupal/admin_toolbar' => '^3.2', // https://www.drupal.org/project/admin_toolbar
    'drupal/config_split' => '^2.0', // https://www.drupal.org/project/config_split
    'drupal/devel' => '^4.1', // https://www.drupal.org/project/devel
    'drupal/coffee' => '^1.2', // https://www.drupal.org/project/coffee
    'drupal/chosen' => '^3.0', // https://www.drupal.org/project/chosen
    'drupal/flood_control' => '^2.2', // https://www.drupal.org/project/flood_control
    'drupal/environment_indicator' => '^4.0', // https://www.drupal.org/project/environment_indicator
    'drupal/svg_image' => '^1.8', // https://www.drupal.org/project/svg_image
    'drupal/svg_image_field' => '^2.1', // https://www.drupal.org/project/svg_image_field
    'drupal/focal_point' => '^1.5', // https://www.drupal.org/project/focal_point
    'drupal/masquerade' => '^2.0@beta', // https://www.drupal.org/project/masquerade
    'drupal/webp' => '^1.0@beta', // https://www.drupal.org/project/webp
    'drupal/password_policy' => '^3.0', // https://www.drupal.org/project/password_policy
    'drupal/seckit' => '^2.0', // https://www.drupal.org/project/seckit
    'drupal/simple_sitemap' => '^4.0', // https://www.drupal.org/project/simple_sitemap
    'drupal/metatag' => '^1.22', // https://www.drupal.org/project/metatag
    'drupal/config_ignore' => '^2.3', // https://www.drupal.org/project/config_ignore
    'drupal/allowed_formats' => '^1.5', // https://www.drupal.org/project/allowed_formats
    'drupal/editor_advanced_link' => '^2.0', // https://www.drupal.org/project/editor_advanced_link
    'drupal/field_group' => '^3.2', // https://www.drupal.org/project/field_group
    'drupal/hide_revision_field' => '^2.2', // https://www.drupal.org/project/hide_revision_field
    'drupal/imagemagick' => '^3.3', // https://www.drupal.org/project/imagemagick
    'drupal/lazy' => '^3.11', // https://www.drupal.org/project/lazy
    'drupal/linkit' => '^6.0', // https://www.drupal.org/project/linkit
    'drupal/mail_login' => '^2.4', // https://www.drupal.org/project/mail_login
    'drupal/maxlength' => '^2.0', // https://www.drupal.org/project/maxlength
    'drupal/media_library_edit' => '^2.2', // https://www.drupal.org/project/media_library_edit
    'drupal/media_responsive_thumbnail' => '^1.2', // https://www.drupal.org/project/media_responsive_thumbnail
    'drupal/paragraphs' => '^1.12', // https://www.drupal.org/project/paragraphs
    'drupal/paragraphs_browser' => '^1.0', // https://www.drupal.org/project/paragraphs_browser
    'drupal/pathauto' => '^1.8', // https://www.drupal.org/project/pathauto
    'drupal/rabbit_hole' => '^1.0@beta', // https://www.drupal.org/project/rabbit_hole
    'drupal/redirect' => '^1.6', // https://www.drupal.org/project/redirect
    'drupal/length_indicator' => '^1.2', // https://www.drupal.org/project/length_indicator
    'drupal/dblog_filter' => '^2.2', // https://www.drupal.org/project/dblog_filter
    'drupal/stage_file_proxy' => '^2.0', // https://www.drupal.org/project/stage_file_proxy
    'drupal/backup_migrate' => '^5.0', // https://www.drupal.org/project/backup_migrate
    'drupal/backup_migrate_aws_s3' => '^5.0', // https://www.drupal.org/project/backup_migrate_aws_s3
    'drupal/ultimate_cron' => '^2.0', // https://www.drupal.org/project/ultimate_cron
    'drupal/quicklink' => '^2.0', // https://www.drupal.org/project/quicklink
    // https://www.drupal.org/project/paragraphs_ee
    // https://www.drupal.org/project/paragraphs_features
    // https://www.drupal.org/project/watchdog_prune
  ],
  'submodules_to_enable' => [
    'admin_toolbar_tools',
    'admin_toolbar_links_access_filter',
  ],
];