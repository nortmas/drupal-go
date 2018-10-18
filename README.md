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
* [Install Docker](https://docs.docker.com/install/linux/docker-ce/ubuntu/)
* [Install Docker Compose](https://docs.docker.com/compose/install/)

## Installation
* To avoid incompatibilities please make sure that all other docker environments are down.
* Set the aliases by adding this line to your `bashrc` or `zshrc` file: `source <path-to-project>/go/scripts/aliases.sh`
* `cp go/makefiles/example.Makefile Makefile && make go_prepare`
*  Now, set the configurations you need in the file `go/go-conf.php` or you can use the default one and just run the next command.
* `make go`

## Aliases
* `god` - Run drush in the php container.
* `godr` - Run drupal in the php container.
* `gor` - Run robo in the php container.
* `goс` - Run composer in the php container.

## Understanding Go configurations
* `project_name` - Will be used for the drupal site name and composer.json project name.
* `project_machine_name` - Will be used for different needs to unify some configurations also will be used as a url prefix.
* `include_basic_modules` - Will include the set of prepared modules and enable them after installation.
* `drush` - Used to configure drush aliases and other useful drush adjustments.
* `multisite` - You can specify the array of needed domains to create the folder structure automatically.
* All other configurations are related to the [docker4drupal](https://github.com/wodby/docker4drupal)

FYI: If you set `memcached` to be enabled, it will also enable memcache drupal module and implement appropriate configurations.

## Available robo commands:
* `robo db_export`, alias `dbe`. Implement an export of current DB state to the DB folder.
* `robo db_import`, alias `dbi`. Implement an import of latest DB dump from the DB folder. `filename` argument is optional to restore the particular dump.
* `robo get_db`, alias `gdb`. Import DB from the specified environment. It requires argument `alias` (dev,stage or prod)
* `robo get_files`, alias `gf`. Import files from the specified environment. It requires argument `alias` (dev,stage or prod)
* `robo rebuild` Execute necessary actions after a pull from the repository.
* `robo multisite` Generate directory structure and necessary configuration files for specified domains.

## Available make commands:
* `make go_up` - Up the docker containers.
* `make go_down` - Stop and remove the docker containers and networks.
* `make go_restart` - Restart containers.
* `make go_reset_structure` - Reset file/directory structure to the initial Drupal Go state.
* `make go_shell` - Get into the php container.
* `make go_code_sniff` - Check codebase with phpcs sniffers to make sure it conforms https://www.drupal.org/docs/develop/standards
* `make go_code_fix` - Fix codebase according to Drupal standards https://www.drupal.org/docs/develop/standards
* `make help` - Shows info about all available make commands.

## How to extend?:
* You can use `Makefile` and `RoboFile.php` to extend with your own project specific commands. 

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
