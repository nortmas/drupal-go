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
1) To avoid incompatibilities please make sure that all other docker environments on your local machine are down.
2) In your `www` directory run `git clone git@github.com:nortmas/drupal-go.git <project-machine-name> && cd <project-machine-name>`.
3) If it's your first project with Drupal GO, for the convenience you may set the aliases (highly recommended):
    * `mkdir ~/.dgo && cp $(pwd)/go/scripts/aliases.sh ~/.dgo/aliases.sh`
    *  Now add this line `source ~/.dgo/aliases.sh` to your `bashrc` or ` bash_profile` or `zshrc` file.
4) If you are a MacOS user, run the following command: `make go_mac`. To improve performance read the [doc](https://wodby.com/docs/stacks/php/local/#user-guided-caching).
5) Now, check the latest tags for the docker images in the [docker4drupal](https://github.com/wodby/docker4drupal/blob/master/.env) and set the configurations you need in the file `GoConfig.php` or use the default settings and just run the next commands one by one:
    * `make go_prepare_env`
    * `make go_drupal_install`
    
## Ongoing project installation
* Get into the project directory: `cd <project-machine-name>`
* Do the steps 1,3,4 from the **Installation** section above.
* Run the environment: `make go_up`.
* Install the dependencies: `goc install`.
* Import data base dump: `gor dbi`.

## Credentials and environment information
* URL: http://`<project_machine_name>`.docker.localhost:8000
* User: `admin`
* Password: `admin`

## How to extend?
* If you need specific settings for your local environment, use the `docker-compose.override.yml` file.
* You can use `Makefile` and `RoboFile.php` to extend workflow with the project specific commands.

## Aliases
* `god` - Run drush in the php container.
* `godr` - Run drupal in the php container.
* `gor` - Run robo in the php container.
* `goс` - Run composer in the php container.

## Understanding Go configurations
* `project_name` - Will be used for the drupal site name and composer.json project name.
* `project_machine_name` - Will be used for different needs to unify some configurations also will be used as a url prefix.
* `port` - The port which will be used for the local site url.
* `include_basic_modules` - Will include the set of prepared modules and enable them after installation.
* `drush` - Used to configure drush aliases and other useful drush adjustments.
* `multisite` - You can specify the array of needed domains to create the folder structure automatically.
* `gitlab` - Configure GitLab CI files for the deployment flow.
* `behat` - Used for Behat configuration files.
* All other configurations are related to the [docker4drupal](https://github.com/wodby/docker4drupal)

FYI: If you set `memcached` to be enabled, it will also enable memcache drupal module and implement appropriate configurations.

## Available make commands:
* `make go_prepare_env` - Roll out the environment.
* `make go_drupal_install` - Install Drupal.
* `make go_mac` - Create .env file with specific settings for Mac.
* `make go_run_behat` - Run behat tests.
* `make go_drupal_update` - Update Drupal core with dependencies.
* `make go_update_translations` - Update Drupal translations.
* `make go_set_files_permissions` - Set right permissions for the files directory.
* `make go_code_sniff` - Check codebase with phpcs sniffers to make sure it conforms https://www.drupal.org/docs/develop/standards
* `make go_code_fix` - Fix codebase according to Drupal standards https://www.drupal.org/docs/develop/standards
* `make go_up` - Up the docker containers.
* `make go_down` - Stop and remove the docker containers and networks.
* `make go_restart` - Restart containers.
* `make go_reset_structure` - Reset file/directory structure to the initial Drupal Go state.
* `make go_sh` - Get into the php container.
* `make help` - Shows info about all available make commands.

## Available robo commands:
* `robo db_export`, alias `dbe`. Implement an export of current DB state to the DB folder.
* `robo db_import`, alias `dbi`. Implement an import of latest DB dump from the DB folder. `filename` argument is optional to restore the particular dump.
* `robo get_db`, alias `gdb`. Import DB from the specified environment. It requires argument `alias` (dev,stage or prod)
* `robo get_files`, alias `gf`. Import files from the specified environment. It requires argument `alias` (dev,stage or prod)
* `robo rebuild` Execute necessary actions after a pull from the repository.
* `robo multisite` Generate directory structure and necessary configuration files for specified domains.
* `robo behat_setup` Set up behat auto tests.
* `robo reconf` Reconfigure settings for the particular set of files. May accept arguments: `drupal`, `drush`, `docker`, `gitlab`, `behat`, `phpunit`. `default` (by default) includes drupal, drush, docker))
* `robo gitlab_ci_setup` Set up GitLab CI flow.
* `robo container_add`, alias `cta`. Argument `container_name` (See **Available extra containers** section). Add container to the docker-compose.override.yml
* `robo container_remove`, alias `ctr`. Argument `container_name` (See **Available extra containers** section). Remove container from the docker-compose.override.yml

## Available extra containers:
* `mailhog`
* `varnish`
* `adminer`
* `pma`
* `solr`
* `redis`
* `node`
* `rsyslog`
* `athenapdf`
* `webgrind`
* `blackfire`

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
