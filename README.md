# Drupal Go

It's a starting point for a new Drupal 8 project. Drupal Go will automatically prepare a local development environment and install Drupal project using best practices. Also, it supplies a number of useful tools/commands/aliases you can work with.

The Drupal Go based on [Composer template for Drupal project](https://github.com/drupal-composer/drupal-project), [Robo](https://robo.li) and [docker4drupal](https://github.com/wodby/docker4drupal).

## Requirements
* [Install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
* [Install Robo](https://github.com/consolidation/Robo#installing)
* [Install Docker](https://docs.docker.com/install/linux/docker-ce/ubuntu/) (Mac OS users can miss this step)
* [Install Docker Compose](https://docs.docker.com/compose/install/)

## Installation
Run the following commands from the project root folder:
* `composer install`
* `robo configure && robo install`

## Available commands:
* `robo db_export` Implement an export of current DB state to the DB folder.
* `robo db_import` Implement an import of latest DB dump from the DB folder.
* `robo rebuild` Execute necessary actions after a pull from the repository.
* `robo get_db` Import DB from the specified environment. It requires argument `alias` (dev,stage,prod)
* `robo get_files` Import files from the specified environment. It requires argument `alias` (dev,stage,prod)
* `robo multisite` Generate directory structure and necessary configuration files for specified domains.


## What does it do?

When installing the given `composer.json` some tasks are taken care of:

* Drupal will be installed in the `web`-directory.
* Autoloader is implemented to use the generated composer autoloader in `vendor/autoload.php`,
  instead of the one provided by Drupal (`web/vendor/autoload.php`).
* Modules (packages of type `drupal-module`) will be placed in `web/modules/contrib/`
* Theme (packages of type `drupal-theme`) will be placed in `web/themes/contrib/`
* Profiles (packages of type `drupal-profile`) will be placed in `web/profiles/contrib/`
* Creates default writable versions of `settings.php` and `services.yml`.
* Creates `sites/default/files`-directory.
* Creates `modules/contrib`, `modules/custom` and `modules/patches` -directories.
* Creates `themes/contrib` and `themes/custom` -directories.
* Latest version of drush is installed locally for use at `vendor/bin/drush`.
* Latest version of DrupalConsole is installed locally for use at `vendor/bin/drupal`.
* Preparation the bunch of modules:
  - adminimal_theme 
  - adminimal_admin_toolbar
  - admin_toolbar
  - devel
  - memcache

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull 
request is often a better solution), you can do so with the 
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra 
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL to patch"
        }
    }
}
```

  




