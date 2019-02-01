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
1) To avoid incompatibilities please make sure that your port `80` is available and all other docker environments on your local machine are stopped. Or run `docker container stop $(docker ps -aq)`
2) In your projects root dirrectory (`www` for example) run `git clone git@github.com:nortmas/drupal-go.git <project-machine-name> && cd <project-machine-name> && rm -r .git`. 
   (NOTE: For the sake of consistency, name your project directory as a project machine name.)
3) If it's your first project with Drupal GO, for the convenience you may set the aliases (highly recommended):
    * `mkdir ~/.dgo && cp $(pwd)/go/scripts/aliases.sh ~/.dgo/aliases.sh`
    * Now add this line `source ~/.dgo/aliases.sh` to your `bashrc` or ` bash_profile` or `zshrc` file.
    * Restart your cli. To restart zsh: `exec zsh`, to restart bash: `exec bash -l`.
4) Check the latest tags for the docker images in the [docker4drupal](https://github.com/wodby/docker4drupal/blob/master/.env).
5) Set the configurations you need in the file `GoConfig.php`.
6) Run `make go_lin` OR `make go_mac`, depends on what OS are you using.
7) Run next commands one by one:
    * `make go_prepare_env`
    * `make go_drupal_install`
    
## Performance for Mac OS users:
If you want to improve performance, please read the [doc](https://wodby.com/docs/stacks/php/local/#user-guided-caching).
    
## Ongoing project installation
* Get into the project directory: `cd <project-machine-name>`
* Do the steps 1,3,6 from the **Installation** section above.
* Run the environment: `make go_up`.
* Install the dependencies: `goc install`.
* Import data base dump: `gor dbi`.
* Implement general rebuild: `gor rebuild`.

## Credentials and environment information
* URL: https://`<project_machine_name>`.docker.localhost
* User: `admin`
* Password: `GoIn2house!`

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
* `include_basic_modules` - Will include the set of prepared modules and enable them after installation.
* `multisite` - You can specify the array of needed domains to create the folder structure automatically.
* `deploy` - Configure GitLab CI files and deployment flow.
* `server` - Configure remote server that will contain dev, stage and prod environments.
* `behat` - Used for Behat configuration files.
* All other configurations are related to the [docker4drupal](https://github.com/wodby/docker4drupal)

FYI: If you set `memcached` to be enabled, it will also enable memcache drupal module and implement appropriate configurations.

## Available make commands:
* `make go_prepare_env` - Roll out the environment.
* `make go_drupal_install` - Install Drupal.
* `make go_mac` - Create .env file with specific settings for Mac.
* `make go_lin` - Create .env file with specific settings for Linux.
* `make go_run_behat` - Run behat tests.
* `make go_drupal_update` - Update Drupal core with dependencies.
* `make go_update_translations` - Update Drupal translations.
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
* `robo set_correct_file_permissions`, alias `scfp`. Set correct file permissions according to the official documentation recommendations.
* `robo set_settings_writable`, alias `ssw`. Set writable permissions for settings files.
* `robo multisite` Generate directory structure and necessary configuration files for specified domains.
* `robo behat_setup` Set up behat auto tests.
* `robo reconf` Reconfigure settings for the particular set of files. May accept arguments: `drupal`, `drush`, `docker`, `gitlab`, `behat`, `phpunit`. `default` (by default) includes drupal, drush, docker))
* `robo deploy_setup` Set up the deployment flow.
* `robo container_add`, alias `cta`. Argument `container_name` (See **Available extra containers** section). Add container to the docker-compose.override.yml
* `robo container_remove`, alias `ctr`. Argument `container_name` (See **Available extra containers** section). Remove container from the docker-compose.override.yml

## Available extra containers:
* `mailhog`
* `varnish`
* `adminer`
* `selenium`
* `pma`
* `solr`
* `redis`
* `node`
* `rsyslog`
* `athenapdf`
* `webgrind`
* `blackfire`
* `emulsify`
* `xdebug`

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
