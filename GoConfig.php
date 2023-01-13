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
    /**
     * Administration.
     */
    'admin_toolbar'                  => '^3.0', // https://www.drupal.org/project/admin_toolbar
    'gin_toolbar'                    => '^1.0', // https://www.drupal.org/project/gin_toolbar
    'devel'                          => '^4.0', // https://www.drupal.org/project/devel
    'coffee'                         => '^1.0', // https://www.drupal.org/project/coffee
    'masquerade'                     => '^2.0', // https://www.drupal.org/project/masquerade
    'flood_control'                  => '^2.0', // https://www.drupal.org/project/flood_control
    'config_pages'                   => '^2.0', // https://www.drupal.org/project/config_pages
    'config_split'                   => '^2.0', // https://www.drupal.org/project/config_split
    'config_ignore'                  => '^2.0', // https://www.drupal.org/project/config_ignore
    'dblog_filter'                   => '^2.0', // https://www.drupal.org/project/dblog_filter
    'watchdog_prune'                 => '^2.0', // https://www.drupal.org/project/watchdog_prune
    'stage_file_proxy'               => '^2.0', // https://www.drupal.org/project/stage_file_proxy
    'backup_migrate'                 => '^5.0', // https://www.drupal.org/project/backup_migrate
    'backup_migrate_aws_s3'          => '^5.0', // https://www.drupal.org/project/backup_migrate_aws_s3
    'environment_indicator'          => '^4.0', // https://www.drupal.org/project/environment_indicator
    'entity_redirect'                => '^2.0', // https://www.drupal.org/project/entity_redirect
    'entity_clone'                   => '^2.0', // https://www.drupal.org/project/entity_clone
    'ultimate_cron'                  => '^2.0', // https://www.drupal.org/project/ultimate_cron
    'customerror'                    => '^1.0', // https://www.drupal.org/project/customerror
    'webform'                        => '^6.0', // https://www.drupal.org/project/webform
    'maillog'                        => '^1.0', // https://www.drupal.org/project/maillog
//  'reroute_email'                  => '^1.0', // https://www.drupal.org/project/reroute_email
//  'mail_safety'                    => '^1.0', // https://www.drupal.org/project/mail_safety
//  'scheduler'                      => '^2.0', // https://www.drupal.org/project/scheduler
//  'entity_type_clone'              => '^1.0', // https://www.drupal.org/project/entity_type_clone
//  'taxonomy_manager'               => '^2.0', // https://www.drupal.org/project/taxonomy_manager
//  'auditfiles'                     => '^4.0', // https://www.drupal.org/project/auditfiles
//  'entity_usage'                   => '^2.0', // https://www.drupal.org/project/entity_usage
//  'paragraphs_usage'               => '^1.0', // https://www.drupal.org/project/paragraphs_usage
    /**
     * System and Development.
     */
    'libraries'                      => '^4.0', // https://www.drupal.org/project/libraries
    'twig_tweak'                     => '^3.0', // https://www.drupal.org/project/twig_tweak
    'twig_field_value'               => '^2.0', // https://www.drupal.org/project/twig_field_value
//  'node_revision_delete'           => '^1.0', // https://www.drupal.org/project/node_revision_delete
//  'smtp'                           => '^1.0', // https://www.drupal.org/project/smtp
//  'mimemail'                       => '^1.0', // https://www.drupal.org/project/mimemail
//  'search_api'                     => '^1.0', // https://www.drupal.org/project/search_api
//  'search_api_exclude_entity'      => '^1.0', // https://www.drupal.org/project/search_api_exclude_entity
//  'session_based_temp_store'       => '^1.0', // https://www.drupal.org/project/session_based_temp_store
//  'tvi'                            => '^4.0', // https://www.drupal.org/project/tvi
//  'views_bulk_operations'          => '^4.0', // https://www.drupal.org/project/views_bulk_operations
//  'views_infinite_scroll'          => '^2.0', // https://www.drupal.org/project/views_infinite_scroll
//  'better_exposed_filters'         => '^6.0', // https://www.drupal.org/project/better_exposed_filters
    /**
     * User Interface.
     */
    'allowed_formats'                => '^1.0', // https://www.drupal.org/project/allowed_formats
    'ajax_loader'                    => '^2.0', // https://www.drupal.org/project/ajax_loader
    'chosen'                         => '^3.0', // https://www.drupal.org/project/chosen
    'linkit'                         => '^6.0', // https://www.drupal.org/project/linkit
    'editor_advanced_link'           => '^2.0', // https://www.drupal.org/project/editor_advanced_link
    'link_field_autocomplete_filter' => '^2.0', // https://www.drupal.org/project/link_field_autocomplete_filter
    'field_group'                    => '^3.0', // https://www.drupal.org/project/field_group
    'dropzonejs'                     => '^4.0', // https://www.drupal.org/project/dropzonejs
    'media_bulk_upload'              => '^3.0', // https://www.drupal.org/project/media_bulk_upload
    'hide_revision_field'            => '^2.0', // https://www.drupal.org/project/hide_revision_field
//  'smart_trim'                     => '^2.0', // https://www.drupal.org/project/smart_trim
//  'maxlength'                      => '^2.0', // https://www.drupal.org/project/maxlength
//  'length_indicator'               => '^1.0', // https://www.drupal.org/project/length_indicator
//  'clientside_validation'          => '^4.0', // https://www.drupal.org/project/clientside_validation
//  'address'                        => '^1.0', // https://www.drupal.org/project/address
//  'cshs'                           => '^4.0', // https://www.drupal.org/project/cshs
//  'ckwordcount'                    => '^2.0', // https://www.drupal.org/project/ckwordcount
//  'telephone_formatter'            => '^2.0', // https://www.drupal.org/project/telephone_formatter
//  'telephone_validation'           => '^2.0', // https://www.drupal.org/project/telephone_validation
//  'key_value_field'                => '^1.0', // https://www.drupal.org/project/key_value_field
//  'color_field'                    => '^3.0', // https://www.drupal.org/project/color_field
//  'single_datetime'                => '^2.0', // https://www.drupal.org/project/single_datetime
//  'jquery_ui_datepicker'           => '^2.0', // https://www.drupal.org/project/jquery_ui_datepicker
//  'menu_item_extras'               => '^2.0', // https://www.drupal.org/project/menu_item_extras
//  'inline_entity_form'             => '^1.0', // https://www.drupal.org/project/inline_entity_form
    /**
     * User Ecosystem.
     */
//  'login_security'                 => '^3.0', // https://www.drupal.org/project/login_security
//  'password_policy'                => '^3.0', // https://www.drupal.org/project/password_policy
//  'mail_login'                     => '^2.0', // https://www.drupal.org/project/mail_login
//  'role_delegation'                => '^1.0', // https://www.drupal.org/project/role_delegation
//  'user_registrationpassword'      => '^2.0', // https://www.drupal.org/project/user_registrationpassword
//  'userprotect'                    => '^1.0', // https://www.drupal.org/project/userprotect
    /**
     * Media Ecosystem.
     */
    'media_library_edit'             => '^2.0', // https://www.drupal.org/project/media_library_edit
    'media_responsive_thumbnail'     => '^1.0', // https://www.drupal.org/project/media_responsive_thumbnail
    'svg_image_field'                => '^2.0', // https://www.drupal.org/project/svg_image_field
    'focal_point'                    => '^1.0', // https://www.drupal.org/project/focal_point
    'imagemagick'                    => '^3.0', // https://www.drupal.org/project/imagemagick
    'lazy'                           => '^3.0', // https://www.drupal.org/project/lazy
    'webp'                           => '^1.0', // https://www.drupal.org/project/webp
//  'media_thumbnails'               => '^1.0', // https://www.drupal.org/project/media_thumbnails
//  'oembed_providers'               => '^2.0', // https://www.drupal.org/project/oembed_providers
    /**
     * Paragraph Ecosystem.
     * More modules here: https://www.drupal.org/project/paragraphs/ecosystem.
     */
    'paragraphs'                     => '^1.0', // https://www.drupal.org/project/paragraphs
    'paragraphs_browser'             => '^1.0', // https://www.drupal.org/project/paragraphs_browser
    'entity_reference_revisions'     => '^1.0', // https://www.drupal.org/project/entity_reference_revisions
    'paragraphs_modal_edit'          => '^1.0', // https://www.drupal.org/project/paragraphs_modal_edit
//  'paragraph_view_mode'            => '^3.0', // https://www.drupal.org/project/paragraph_view_mode
//  'paragraphs_sets'                => '^2.0', // https://www.drupal.org/project/paragraphs_sets
//  'paragraphs_ee'                  => '^2.0', // https://www.drupal.org/project/paragraphs_ee
//  'paragraphs_features'            => '^2.0', // https://www.drupal.org/project/paragraphs_features
//  'layout_paragraphs'              => '^2.0', // https://www.drupal.org/project/layout_paragraphs
//  'paragraph_blocks'               => '^3.0', // https://www.drupal.org/project/paragraph_blocks
    /**
     * SEO and Security.
     */
    'seckit'                         => '^2.0', // https://www.drupal.org/project/seckit
    'simple_sitemap'                 => '^4.0', // https://www.drupal.org/project/simple_sitemap
    'metatag'                        => '^1.0', // https://www.drupal.org/project/metatag
    'schema_metatag'                 => '^2.0', // https://www.drupal.org/project/schema_metatag
    'pathauto'                       => '^1.0', // https://www.drupal.org/project/pathauto
    'rabbit_hole'                    => '^1.0', // https://www.drupal.org/project/rabbit_hole
    'redirect'                       => '^1.0', // https://www.drupal.org/project/redirect
    'eu_cookie_compliance'           => '^1.0', // https://www.drupal.org/project/eu_cookie_compliance
    'google_analytics'               => '^4.0', // https://www.drupal.org/project/google_analytics
//  'google_tag'                     => '^1.0', // https://www.drupal.org/project/google_tag
//  'antibot'                        => '^1.0', // https://www.drupal.org/project/antibot
//  'honeypot'                       => '^2.0', // https://www.drupal.org/project/honeypot
//  'captcha'                        => '^2.0', // https://www.drupal.org/project/captcha
    /**
     * Performance.
     */
    'advagg'                         => '^2.0', // https://www.drupal.org/project/advagg
    'warmer'                         => '^2.0', // https://www.drupal.org/project/warmer
    'quicklink'                      => '^2.0', // https://www.drupal.org/project/quicklink
    'big_pipe_sessionless'           => '^2.0', // https://www.drupal.org/project/big_pipe_sessionless
//  'fast_404'                       => '^2.0', // https://www.drupal.org/project/fast_404
//  'purge'                          => '^3.0', // https://www.drupal.org/project/purge
    /**
     * Content access and Permissions.
     */
//  'view_unpublished'               => '^1.0', // https://www.drupal.org/project/view_unpublished
//  'access_unpublished'             => '^1.0', // https://www.drupal.org/project/access_unpublished
//  'simple_menu_permissions'        => '^1.0', // https://www.drupal.org/project/simple_menu_permissions
    /**
     * Migration.
     */
//   'migrate_file'                  => '^2.0', // https://www.drupal.org/project/migrate_file
//   'migrate_plus'                  => '^5.0', // https://www.drupal.org/project/migrate_plus
//   'migrate_tools'                 => '^5.0', // https://www.drupal.org/project/migrate_tools
    /**
     * Languages and Translations.
     */
//  'potx'                                      => '^5.0', // https://www.drupal.org/project/potx
//  'paragraphs_asymmetric_translation_widgets' => '^1.0', // https://www.drupal.org/project/paragraphs_asymmetric_translation_widgets
  ],
  'submodules_to_enable' => [
    'admin_toolbar_tools',
    'admin_toolbar_links_access_filter',
  ],
];