# Drupal Go

It's a starting point for a new Drupal 8 project. Drupal Go will automatically prepare a local development environment and install Drupal project using best practices. Also, it supplies a number of useful tools/commands/aliases you can work with.

The Drupal Go based on [Composer template for Drupal project](https://github.com/drupal-composer/drupal-project), [Robo](https://robo.li) and [docker4drupal](https://github.com/wodby/docker4drupal).

## What does it do?
* Prepare the development environment based on [docker4drupal](https://github.com/wodby/docker4drupal)
* Prepare the project folder structure based on best practices form [Composer template for Drupal project](https://github.com/drupal-composer/drupal-project).
* Automatically installs a drupal Project based on custom configurations.
* Provides useful tools and commands.
* Generates drush aliases and applies correct adjustments for drush configurations.

## Requirements
* [Install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
* [Install Robo](https://github.com/consolidation/Robo#installing)
* [Install Docker](https://docs.docker.com/install/linux/docker-ce/ubuntu/) (Mac OS users can miss this step)
* [Install Docker Compose](https://docs.docker.com/compose/install/)

## Installation
You can  either configure your project beforehand, to do so you need to create a config file:
* `cp go/example.go-conf.php go/go-conf.php` - Now, set the configurations you need and proceed with the next steps.

Or you can skip previous step and use default configurations:
* `cp go/example.RoboFile.php ./RoboFile.php`
* `composer install && robo go`


## Understanding Go configurations
* `project_name` - Will be used for the drupal site name and composer.json project name.
* `project_machine_name` - Will be used for differnet needs to unify some configurations also will be used as a url prefix.
* `include_basic_modules` - Will include the set of prepared modules and enable them after installation.
* `drush` - Used to configure drush aliases and other useful drush adjustments.
* `multisite` - You can specify the array of needed domains to create the folder structure automatically.
* All other configurations are related to the [docker4drupal](https://github.com/wodby/docker4drupal)

FYI: If you set `memcached` to be enabled, it will also enable memcache drupal module and implement appropriate configurations.

## Available commands:
* `robo db_export` Implement an export of current DB state to the DB folder.
* `robo db_import` Implement an import of latest DB dump from the DB folder.
* `robo rebuild` Execute necessary actions after a pull from the repository. (Requires module config_split)
* `robo get_db` Import DB from the specified environment. It requires argument `alias` (dev,stage,prod)
* `robo get_files` Import files from the specified environment. It requires argument `alias` (dev,stage,prod)
* `robo multisite` Generate directory structure and necessary configuration files for specified domains.

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
