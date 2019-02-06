<?php

require_once $realDir . '/vendor/autoload.php';
require_once $realDir . '/web/core/includes/bootstrap.inc';
require_once $realDir . '/web/core/includes/install.inc';

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Robo\Tasks;
use Drupal\Component\PhpStorage\FileStorage;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * This is Drupal GO's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class GoRoboFile extends Tasks {

  use Boedah\Robo\Task\Drush\loadTasks;

  /** var string */
  protected $projectRoot;
  /** var string */
  protected $goRoot;
  /** var string */
  protected $drupalRoot;
  /** var string */
  protected $defaultSettingsPath;
  /** var array */
  protected $config;
  /** @var \Symfony\Component\Filesystem\Filesystem */
  protected $fileSystem;

  public function __construct() {
    $this->fileSystem = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $this->projectRoot = $drupalFinder->getComposerRoot();
    $this->goRoot = $this->projectRoot . '/go';
    $this->drupalRoot = $drupalFinder->getDrupalRoot();
    $this->defaultSettingsPath = $this->drupalRoot . '/sites/default';
    $this->config = $this->getConfig();
  }

  /**
   * Test function.
   */
  public function test() {
    //$this->deploy_setup();
    $this->yell('Hello!');
  }

  /**
   * Prepare
   */
  public function prepare() {
    if ($this->config['include_basic_modules'] == FALSE) {
      $basic_modules = $this->ask("Do you want to include basic modules? Y or N");
      $this->config['include_basic_modules'] = strtolower($basic_modules) == 'y' ? 1 : 0;
      $this->updateGoConf();
    }

    if ($this->config['memcached']['enable'] == FALSE) {
      $memcached = $this->ask("Do you want to set up memcached? Y or N");
      $this->config['memcached']['enable'] = strtolower($memcached) == 'y' ? 1 : 0;
      $this->updateGoConf();
    }

    if ($this->config['behat']['enable'] == FALSE) {
      $behat = $this->ask("Do you want to set up behat tests? Y or N");
      $this->config['behat']['enable'] = strtolower($behat) == 'y' ? 1 : 0;
      $this->updateGoConf();
    }

    if ($this->config['deploy']['enable'] == FALSE) {
      $behat = $this->ask("Do you want to set up deployment flow? Y or N");
      $this->config['deploy']['enable'] = strtolower($behat) == 'y' ? 1 : 0;
      $this->updateGoConf();
    }

    $this->configureProject(TRUE);
  }

  /**
   * Roll out the whole project.
   */
  public function go() {
    $this->install();
    $this->prepareComposerJson();
    if ($this->config['include_basic_modules'] == TRUE) {
      $this->installBasicModules();
    }
    if ($this->config['memcached']['enable'] == TRUE) {
      $this->setUpMemcache();
    }
    if ($this->config['behat']['enable'] == TRUE) {
      $this->behat_setup();
    }
    if ($this->config['deploy']['enable'] == TRUE) {
      $this->deploy_setup();
    }
    $this->removeNeedlessModules();
    $message = 'Available domains: ';
    $domains = $this->getDomains();
    foreach (explode(',', $domains) as $domain) {
      $message .= "\n" . 'https://' . $domain;
    }
    $this->yell($message);
  }

  /**
   * Install Drupal.
   *
   * @param string $profile
   */
  public function install($profile = 'standard') {
    $this->createSettingsFile();

    $drush_install = $this->taskDrushStack()
      ->siteName($this->config['project_name'])
      ->siteMail($this->config['project_machine_name'] . '@example.com')
      ->locale('en')
      ->sitesSubdir('default')
      ->accountMail('admin@example.com')
      ->accountName('admin')
      ->accountPass('GoIn2house!')
      ->mysqlDbUrl(getenv('DB_USER') . ':' . getenv('DB_PASSWORD') . '@mariadb:3306/' . getenv('DB_NAME'))
      ->disableUpdateStatusModule()
      ->siteInstall($profile)
      ->getCommand();

    $this->commandExec($drush_install);
    $this->updateSettingsFile();
  }

  /**
   * Reinstall Drupal from the scratch.
   * @aliases rei
   */
  public function reinstall() {
    $drush_drop = $this->taskDrushStack()->drush('sql-drop')->getCommand();
    $this->commandExec($drush_drop);
    $file_settings = $this->defaultSettingsPath . '/settings.php';
    $docker_settings = $this->defaultSettingsPath . '/settings.docker.php';
    $this->fileSystem->chmod($this->defaultSettingsPath, 0775);
    $this->fileSystem->chmod($file_settings, 0664);
    $this->fileSystem->remove($file_settings);
    $this->say("Remove ' . $file_settings . ' file.");
    $this->fileSystem->chmod($docker_settings, 0664);
    $this->fileSystem->remove($docker_settings);
    $this->say("Remove ' . $docker_settings . ' file.");
    $this->prepare();
    $this->go();
  }

  /**
   * Create file structure and configure project for Docker.
   */
  public function conf() {
    $this->configureProject();
  }

  /**
   * Recreate configuration files.
   * @param string $set The name of set of the files to be recreated.
   *   May have values: drupal, drush, docker, deploy, behat, phpunit.
   */
  public function reconf($set = 'default') {
    $this->io()->caution("This action will overwrite all previously created configs.");
    $save_origin = $this->ask("Do you want to save backups if they differ from the original ones? Y or N");
    $save_origin = strtolower($save_origin) == 'y' ? TRUE : FALSE;
    foreach ($this->getFiles($set) as $options) {
      $this->makeFileTemplate($options, TRUE, $save_origin);
    }
  }

  /**
   * Reset directory structure.
   */
  public function reset_file_structure() {
    $this->io()->caution("This action will remove all automatically created folders and reset Drupal GO to the default state.");
    $ask = $this->ask("Do you want to continue? Y or N");
    $do = strtolower($ask) === 'y' ? TRUE : FALSE;
    if ($do) {
      $this->fileSystem->chmod($this->defaultSettingsPath, 0775);
      $this->fileSystem->remove([
        $this->projectRoot . '/traefik.toml',
        $this->projectRoot . '/.gitlab-ci.yml',
        $this->projectRoot . '/drush',
        $this->projectRoot . '/composer.lock',
        //$this->projectRoot . '/certs',
        $this->projectRoot . '/deploy',
        $this->projectRoot . '/config',
        $this->projectRoot . '/db',
        $this->projectRoot . '/tests',
        $this->projectRoot . '/patches',
      ]);
    }
  }

  /**
   * Generates folders and configurations for multi-site usage.
   */
  public function multisite() {
    $this->setUpMultisite();
  }

  /**
   * Set up behat auto tests.
   *
   * @aliases bhs
   */
  public function behat_setup() {

    if (!$this->fileSystem->exists($this->projectRoot . '/tests/behat')) {
      $this->_copyDir($this->projectRoot . '/go/behat', $this->projectRoot . '/tests/behat');
      // Add behat files using prepared templates.
      foreach ($this->getFiles('behat') as $options) {
        $this->makeFileTemplate($options, FALSE);
      }
      $this->say('The folder ' . $this->projectRoot . '/tests/behat has been created.');

      $modules = [
        "behat/mink-zombie-driver" => "^1.4",
        "devinci/devinci-behat-extension" => "dev-master",
        "drupal/drupal-extension" => "^3.4",
        "emuse/behat-html-formatter" => "^0.1.0",
        "jarnaiz/behat-junit-formatter" => "^1.3",
      ];
      foreach ($modules as $name => $version) {
        $this->taskComposerRequire()->dependency($name, $version)->dev()->run();
      }

      if ($this->config['behat']['enable'] == FALSE) {
        $this->config['behat']['enable'] = 1;
        $this->updateGoConf();
        $this->reconf('docker');
        $this->io()->caution('Selenuum container has been added. Now you must restart the containers. Run: make go_restart.');
      }
    }
  }

  /**
   * Set up the deployment flow.
   *
   * @aliases gcs
   */
  public function deploy_setup() {
    if (!$this->fileSystem->exists($this->projectRoot . '/deploy')) {
      // Using prepared templates.
      $deploy_files = $this->getFiles(['deploy', 'docker_deploy']);
      foreach ($deploy_files as $options) {
        $this->makeFileTemplate($options, FALSE);
      }
      if ($this->config['deploy']['enable'] == FALSE) {
        $this->config['deploy']['enable'] = 1;
        $this->updateGoConf();
      }
      $this->say('The files ' . implode(', ', array_column($deploy_files, 'path')) . ' has been created.');
    }
  }

  /**
   * Implement an export of current DB state to the DB folder.
   *
   * @aliases dbe
   */
  public function db_export() {
    if (!$this->fileSystem->exists($this->projectRoot . '/db')) {
      $this->fileSystem->mkdir($this->projectRoot . '/db');
    }
    $file_name = '../db/' . date('d.m.Y-h.i.s') . '.sql';
    $drush_db_exp = $this->taskDrushStack()
      ->siteAlias('@self')
      ->drush('sql-dump --gzip --structure-tables-key=common --result-file=' . $file_name)
      ->getCommand();
    $this->commandExec($drush_db_exp);
  }

  /**
   * Implement an import of latest DB dump from the DB folder.
   *
   * @aliases dbi
   *
   * @param $file_name
   *  The name of the dump file to restore.
   */
  public function db_import($file_name = NULL) {
    // Drop DB
    $drush_drop = $this->taskDrushStack()->drush('sql-drop')->getCommand();
    $this->commandExec($drush_drop);

    // Get last db dump.
    if (!$file_name) {
      $file_name = $this->taskExec('ls db/* | sort -k1 -r| head -1')
        ->interactive(FALSE)
        ->run()->getMessage();
      $file_name = trim(preg_replace('/\s+/', ' ', $file_name));
    }
    else {
      $file_name = 'db/' . $file_name;
    }

    // Import DB
    $this->commandExec('sh -c "gunzip -c ' . $file_name . ' | drush @self sqlc"');
  }

  /**
   * Execute necessary actions after a pull from the repository.
   */
  public function rebuild() {
    $drush_cc_drush = $this->taskDrushStack()->clearCache('drush')->getCommand();
    $drush_cim = $this->taskDrushStack()->drush('cim')->getCommand();
    $drush_updb = $this->taskDrushStack()->drush('updb')->getCommand();
    $drush_eu = $this->taskDrushStack()->drush('entity-updates')->getCommand();
    $composer_install = $this->taskComposerInstall()->getCommand();

    $this->commandExec($drush_cc_drush);
    $this->commandExec($drush_cim);
    $this->commandExec($composer_install);
    $this->commandExec($drush_updb);
    $this->commandExec($drush_cim);
    $this->commandExec($drush_eu);
  }

  /**
   * Import DB from the specified environment.
   *
   * @aliases gdb
   * @param $alias - dev,stage or prod
   * @param $cim - whether to apply cim after db import
   */
  public function get_db($alias, $cim = TRUE) {
    $alias = '@' . $this->config['project_machine_name'] . '.' . $alias;
    $drush_create_db = $this->taskDrushStack()->drush('sql-create')->getCommand();
    $drush_sync = $this->taskDrushStack()->drush('sql-sync --source-dump=/tmp/db.sql ' . $alias . ' @self')->getCommand();

    $this->commandExec($drush_create_db);
    $this->commandExec($drush_sync);

    if ($cim) {
      $drush_csim = $this->taskDrushStack()->drush('cim')->getCommand();
      $this->commandExec($drush_csim);
    }
  }

  /**
   * Export DB to the specified environment.
   *
   * @aliases pdb
   * @param $alias - dev,stage or prod
   */
  public function push_db($alias) {
    $alias = '@' . $this->config['project_machine_name'] . '.' . $alias;
    $drush_create_db = $this->taskDrushStack()->drush($alias . 'sql-create')->getCommand();
    $drush_sync = $this->taskDrushStack()->drush('sql-sync --source-dump=/tmp/db.sql @self ' . $alias)->getCommand();
    $drush_csim = $this->taskDrushStack()->drush($alias . ' cim')->getCommand();

    $this->commandExec($drush_create_db);
    $this->commandExec($drush_sync);
    $this->commandExec($drush_csim);
  }

  /**
   * Import files from the specified environment.
   *
   * @aliases gf
   * @param $alias - dev,stage or prod
   */
  public function get_files($alias) {
    $alias = '@' . $this->config['project_machine_name'] . '.' . $alias . '-files';
    $drush_sync = $this->taskDrushStack()->drush('rsync ' . $alias . ':%files/ @self:%files')->getCommand();
    $this->commandExec($drush_sync);
  }

  /**
   * Export files to the specified environment.
   *
   * @aliases pf
   * @param $alias - dev,stage or prod
   */
  public function push_files($alias) {
    $alias = '@' . $this->config['project_machine_name'] . '.' . $alias . '-files';
    $drush_sync = $this->taskDrushStack()->drush('rsync @self:%files/ ' . $alias . ':%files ')->getCommand();
    $this->commandExec($drush_sync);
  }

  /**
   * Wrapper to execute command inside container.
   *
   * @param $command string
   */
  protected function commandExec($command) {
    $this->taskExec($command)->interactive(FALSE)->run()->getMessage();
  }

  /**
   * Configure Drupal Project for Docker.
   *
   * @param bool $ovewrite
   *
   * @throws \Exception
   */
  protected function configureProject($ovewrite = FALSE) {
    $dirs = [
      $this->projectRoot . '/config' => 0,
      //$this->projectRoot . '/certs' => 1,
      $this->projectRoot . '/config/default' => 1,
      $this->projectRoot . '/config/local' => 1,
      $this->projectRoot . '/config/dev' => 1,
      $this->projectRoot . '/config/stage' => 1,
      $this->projectRoot . '/config/prod' => 1,
      $this->projectRoot . '/db' => 1,
      $this->projectRoot . '/tests' => 1,
      $this->projectRoot . '/patches' => 1,
      $this->drupalRoot . '/profiles' => 1,
      $this->drupalRoot . '/modules' => 0,
      $this->drupalRoot . '/themes' => 0,
      $this->drupalRoot . '/modules/custom' => 1,
      $this->drupalRoot . '/modules/contrib' => 1,
      $this->drupalRoot . '/themes/custom' => 1,
      $this->drupalRoot . '/themes/contrib' => 1,
      $this->drupalRoot . '/sites/default/files' => 1,
      $this->drupalRoot . '/sites/default/files/tmp' => 2,
      $this->drupalRoot . '/sites/default/files/private' => 3,
    ];

    foreach ($dirs as $dir => $mode) {
      $this->mkDir($dir, $mode);
    }

    // Add necessary configuration files using prepared templates.
    foreach ($this->getFiles() as $options) {
      $this->makeFileTemplate($options, $ovewrite);
    }
  }

  /**
   * Set Up multisite.
   */
  protected function setUpMultisite() {

    if (!empty($this->config['multisite'])) {

      foreach ($this->config['multisite'] as $site_name) {

        $dirs = [
          $this->drupalRoot . '/config/' . $site_name . '/default' => 1,
          $this->drupalRoot . '/config/' . $site_name . '/local' => 1,
          $this->drupalRoot . '/config/' . $site_name . '/dev' => 1,
          $this->drupalRoot . '/config/' . $site_name . '/stage' => 1,
          $this->drupalRoot . '/config/' . $site_name . '/prod' => 1,
          $this->drupalRoot . '/sites/' . $site_name . '/modules' => 0,
          $this->drupalRoot . '/sites/' . $site_name . '/themes' => 0,
          $this->drupalRoot . '/sites/' . $site_name . '/modules/custom' => 1,
          $this->drupalRoot . '/sites/' . $site_name . '/themes/custom' => 1,
          $this->drupalRoot . '/sites/' . $site_name . '/modules/contrib' => 1,
          $this->drupalRoot . '/sites/' . $site_name . '/themes/contrib' => 1,
        ];

        // Create site directory.
        $this->mkDir($this->drupalRoot . '/sites/' . $site_name);

        // Create all site subdirectories.
        foreach ($dirs as $dir => $mode) {
          $this->mkDir($dir, $mode);
        }

        $file_settings = $this->drupalRoot . '/sites/'. $site_name . '/settings.php';
        $file_def_settings = $this->defaultSettingsPath . '/default.settings.php';

        $settings['config_directories'] = [
          CONFIG_SYNC_DIRECTORY => (object) [
            'value' => Path::makeRelative($this->projectRoot . '/config/' . $site_name . '/default/', $this->drupalRoot),
            'required' => TRUE,
          ],
        ];

        $this->createSettingsFile($file_settings, $file_def_settings, $settings);
        $this->updateSettingsFile($file_settings);
      }

      // Create sites configuration file.
      foreach ($this->getFiles('multisite') as $options) {
        $this->makeFileTemplate($options);
      }
    }
  }

  /**
   * Create settings.php
   *
   * @param $file_settings
   * @param $file_def_settings
   * @param $settings
   */
  protected function createSettingsFile($file_settings = NULL, $file_def_settings = NULL, $settings = NULL) {
    $file_settings = $file_settings ?: $this->defaultSettingsPath . '/settings.php';
    $file_def_settings = $file_def_settings ?: $this->defaultSettingsPath . '/default.settings.php';

    if (!$settings) {
      $settings['settings']['hash_salt'] = (object) [
        'value' => Crypt::randomBytesBase64(55),
        'required' => TRUE,
      ];
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($this->projectRoot . '/config/default', $this->drupalRoot),
          'required' => TRUE,
        ],
      ];
    }

    // Prepare the settings file for installation
    if (!$this->fileSystem->exists($file_settings) && $this->fileSystem->exists($file_def_settings)) {
      $this->fileSystem->copy($file_def_settings, $file_settings);
      #$this->fileSystem->chmod($file_settings, 0666);

      // Initialize the settings from the recently created settings.php
      $class_loader = require_once $this->projectRoot . '/vendor/autoload.php';
      Settings::initialize($this->drupalRoot, 'default', $class_loader);
      // Override settings.php with new configurations.
      drupal_rewrite_settings($settings, $file_settings);
      $this->say("Create a ' . $file_settings . ' file with mode 666");
    }
  }

  /**
   * Update settings.php
   *
   * @param $file_settings
   */
  protected function updateSettingsFile($file_settings = NULL) {
    $file_settings = $file_settings ?: $this->defaultSettingsPath . '/settings.php';

    if (!is_writable($this->defaultSettingsPath)) {
      $this->fileSystem->chmod($this->defaultSettingsPath, 0775);
    }

    // Make sure that settings.docker.php gets called from settings.php.
    if (!$this->fileSystem->exists($file_settings)) {
      $this->createSettingsFile($file_settings);
    }

    if (!is_writable($file_settings)) {
      $this->fileSystem->chmod($file_settings, 0664);
    }

    $settings_content = file_get_contents($file_settings);

    if (strpos($settings_content, "'database' => 'drupal'") !== FALSE) {
      $this->fileSystem->remove($file_settings);
      $this->createSettingsFile($file_settings);
      $settings_content = file_get_contents($file_settings);
    }

    if (strpos($settings_content, 'settings.docker.php') === FALSE) {

      $relative_path = Path::makeRelative($this->defaultSettingsPath, $this->drupalRoot);

      $append = <<<EOT

if (file_exists(\$app_root . '/$relative_path/settings.docker.php')) {
  if (isset(\$_ENV['GIT_USER_NAME']) && \$_ENV['GIT_USER_NAME'] === 'wodby') {
    // LOCAL environment
    include \$app_root . '/$relative_path/settings.docker.php';
  }
  elseif (isset(\$_ENV['GIT_USER_NAME']) && \$_ENV['GIT_USER_NAME'] === 'wodby-dev') {
    // DEV environment
    include \$app_root . '/$relative_path/settings.docker.php';
  }
}

if (file_exists(\$app_root . '/$relative_path/settings.prod.php')) {
  if (isset(\$_ENV['GIT_USER_NAME']) && \$_ENV['GIT_USER_NAME'] === 'wodby-stage') {
    // STAGE environment
    include \$app_root . '/$relative_path/settings.prod.php';
  }
  elseif (isset(\$_ENV['GIT_USER_NAME']) && \$_ENV['GIT_USER_NAME'] === 'wodby-master') {
    // PRODUCTION environment
    include \$app_root . '/$relative_path/settings.prod.php';
  }
}

EOT;
      $this->fileSystem->appendToFile($file_settings, $append);
    }
  }

  /**
   * Set correct file permissions according to the official documentation recommendations.
   * https://www.drupal.org/node/244924
   *
   * @aliases scp
   *
   * @param bool $sudo
   *   Shows whether the commands supposed to be run as sudo or not.
   * @param string $user
   * @param string $group
   */
  public function set_correct_permissions($sudo = TRUE, $user = 'wodby', $group = 'www-data') {

    $sudo_word = $sudo ? 'sudo ' : '';

    $dirs = [
      $this->drupalRoot . '/sites/default/files' => 1,
      $this->drupalRoot . '/sites/default/files/tmp' => 2,
      $this->drupalRoot . '/sites/default/files/private' => 3,
    ];

    foreach ($dirs as $dir => $mode) {
      $this->mkDir($dir, $mode);
    }

    if ($user && $group) {
      $this->say('Changing ownership of all contents inside ' . $this->projectRoot);
      $this->setOwnership($this->projectRoot, $user, $group, $sudo);
    }

    $this->say('Changing permissions of all directories inside ' . $this->projectRoot);
    $this->commandExec($sudo_word . 'find ' . $this->projectRoot . ' -type d -exec chmod u=rwx,g=rx,o=rx \'{}\' \;');

    $this->say('Changing permissions of all files inside ' . $this->projectRoot);
    $this->commandExec($sudo_word . 'find ' . $this->projectRoot . ' -type f -exec chmod u=rw,g=r,o=r \'{}\' \;');

    $this->commandExec('nohup chmod 444 ' . $this->defaultSettingsPath . '/settings.docker.php  > /dev/null');
    $this->commandExec('nohup chmod 444 ' . $this->defaultSettingsPath . '/settings.prod.php  > /dev/null');
    $this->commandExec('nohup chmod 444 ' . $this->defaultSettingsPath . '/settings.php  > /dev/null');

    $this->setPermissions($this->projectRoot . '/config', '2755', $sudo);
    $this->setPermissions($this->defaultSettingsPath . '/files', '2775', $sudo);

    $this->say('Changing permissions of all directories inside ' . $this->projectRoot . '/vendor');
    $this->setPermissions($this->projectRoot . '/vendor', '2755', $sudo);
    $this->setPermissions($this->projectRoot . '/drush/drush-run.sh', '2755', $sudo);

    $this->commandExec($sudo_word . 'find ' . $this->drupalRoot . ' -name ".htaccess" -type f -exec chmod u=rw,g=r,o=r \'{}\' \;');
  }

  /**
   * Set writable permissions for settings files.
   *
   * @aliases ssw
   */
  public function set_settings_writable() {
    $this->commandExec('nohup chmod 644 ' . $this->defaultSettingsPath . '/settings.docker.php  > /dev/null');
    $this->commandExec('nohup chmod 644 ' . $this->defaultSettingsPath . '/settings.prod.php  > /dev/null');
    $this->commandExec('nohup chmod 644 ' . $this->defaultSettingsPath . '/settings.php  > /dev/null');
  }

  /**
   * Set subdirectory ownership.
   * Taken from the module: https://www.drupal.org/project/file_permissions
   *
   * @param $directory
   * @param $user
   * @param $group
   * @param bool $sudo
   *   Shows whether the commands supposed to be run as sudo or not.
   */
  protected function setOwnership($directory, $user, $group, $sudo = TRUE) {
    // Set file permissions.
    // The 2 means that the group id will be preserved for any new files created
    // in this directory. What that means is that `www-data` will always be the
    // group on any files, thereby ensuring that web server and the user will both
    // always have write permissions to any new files that are placed in this
    // directory.
    $sudo = $sudo ? 'sudo ' : '';
    $this->commandExec('nohup ' . $sudo . 'chown -Rv ' . $user . ':' . $group . ' ' . $directory . '  > /dev/null');
    $this->commandExec('nohup ' . $sudo . 'chgrp -Rv ' . $group . ' ' . $directory . '  > /dev/null');
  }

  /**
   * Set subdirectory permissions.
   * Taken from the module: https://www.drupal.org/project/file_permissions
   *
   * @param $directory
   * @param string $mode
   * @param bool $sudo
   *   Shows whether the commands supposed to be run as sudo or not.
   */
  protected function setPermissions($directory, $mode = '2775', $sudo = TRUE) {
    $sudo = $sudo ? 'sudo ' : '';
    $this->commandExec('nohup ' . $sudo . 'chmod -R ' . $mode . ' ' . $directory . '  > /dev/null');
  }

  /**
   * Preapare yml file to add only xdebug configurations.
   * @param array $yaml
   */
  protected function containerAddXdebug(&$yaml) {
    unset($yaml['services']['php']['image']);
    unset($yaml['services']['php']['volumes']);
    foreach ($yaml['services']['php']['environment'] as $key => $value) {
      if (!strstr($key, 'PHP_')) {
        unset($yaml['services']['php']['environment'][$key]);
      }
    }
  }

  /**
   * Add container to the docker-compose.override.yml
   * @aliases cta
   * @param string $container_name
   */
  public function container_add($container_name) {
    $twig_loader = new \Twig_Loader_Array([]);
    $twig = new \Twig_Environment($twig_loader);

    $dc_file_name = 'docker-compose.yml';
    $dco_file_name = 'docker-compose.override.yml';

    $temp_conf = $this->config;

    if ($container_name == 'adminer' || $container_name ==  'pma') {
      $temp_conf['dbbrowser']['enable'] = 1;
      $temp_conf['dbbrowser']['type'] = $container_name;
    }
    elseif ($container_name == 'selenium') {
      $temp_conf['behat']['enable'] = 1;
    }
    elseif ($container_name == 'xdebug') {
      $temp_conf['php']['xdebug'] = 1;
    }
    else {
      $temp_conf[$container_name]['enable'] = 1;
    }

    $twig_loader->setTemplate($dc_file_name, file_get_contents($this->goRoot . '/templates/docker/' . $dc_file_name . '.twig'));
    $rendered = $twig->render($dc_file_name, $temp_conf);
    $yaml = Yaml::parse($rendered);

    if ($container_name == 'xdebug') {
      $container_name = 'php';
      $this->containerAddXdebug($yaml);
    }

    if (!$this->fileSystem->exists($dco_file_name)) {
      $override_yaml['version'] = $yaml['version'];
      $override_yaml['services'][$container_name] = $yaml['services'][$container_name];
    }
    else {
      $override_yaml = Yaml::parse(file_get_contents($dco_file_name));
      $override_yaml['services'][$container_name] = $yaml['services'][$container_name];
    }

    $rendered = Yaml::dump($override_yaml, 9, 2);
    file_put_contents($dco_file_name, $rendered);
    $this->say("New containers has been added to the " . $dco_file_name);
  }

  /**
   * Remove container from the docker-compose.override.yml
   * @aliases ctr
   * @param string $container_name
   */
  public function container_remove($container_name) {
    $dco_file_name = 'docker-compose.override.yml';
    if ($this->fileSystem->exists($dco_file_name)) {
      $override_yaml = Yaml::parse(file_get_contents($dco_file_name));
      if ($container_name == 'xdebug') {
        unset($override_yaml['services']['php']);
      }
      else {
        unset($override_yaml['services'][$container_name]);
      }
      $rendered = Yaml::dump($override_yaml, 9, 2);
      file_put_contents($dco_file_name, $rendered);
      $this->say("The " . $container_name . " has been removed from " . $dco_file_name);
    }
  }

  /**
   * Get the list of domains as a string with delimetr ",".
   * @param bool $server
   * @param bool $service
   */
  protected function getDomains($server = FALSE, $service = FALSE) {
    $main_domain = $server ? '.dev.' . $this->config['server']['domain'] : '.docker.localhost';
    if (!$service) {
      if (!empty($this->config['multisite'])) {
        $domains = implode($main_domain . ',', array_keys($this->config['multisite']));
      }
      else {
        $domains = $this->config['project_machine_name'];
      }
      $domains .= $main_domain;
      return $domains;
    }
    else {
      return $this->config['project_machine_name'] . $main_domain;
    }
  }

  /**
   * Create file using prepared templates.
   *
   * @param $options
   * @param bool $overwrite
   * @param bool $save_origin
   */
  protected function makeFileTemplate($options, $overwrite = FALSE, $save_origin = FALSE) {
    $twig_loader = new \Twig_Loader_Array([]);
    $twig = new \Twig_Environment($twig_loader);

    $template = basename($options['path']);

    if (!$this->fileSystem->exists($options['dest'])) {
      $this->fileSystem->mkdir($options['dest']);
    }

    $global_conf = $this->config;
    if (isset($options['vars'])) {
      $global_conf += $options['vars'];
      if (isset($options['vars']['deploy_version'])) {
        $global_conf['php']['tag'] = str_replace('${OS}', '', $global_conf['php']['tag']);
      }
    }

    if ($template == 'docker-compose.yml') {
      $global_conf['deploy_version'] = isset($options['vars']['deploy_version']) ?: FALSE;
      $global_conf['domains'] = $this->getDomains($global_conf['deploy_version']);
      $global_conf['service_domain'] = $this->getDomains($global_conf['deploy_version'], TRUE);
      $this->config += $global_conf;
    }

    $file = !empty($options['rename']) ? $options['dest'] . '/' . $options['rename'] : $options['dest'] . '/' . $template;

    if (!$this->fileSystem->exists($file) || $overwrite) {
      $twig_loader->setTemplate($template, file_get_contents($this->goRoot . '/templates/' . $options['path'] . '.twig'));
      $rendered = $twig->render($template, $global_conf);

      if ($this->fileSystem->exists($file) && $save_origin) {
        if (md5_file($file) == md5($rendered)) {
          return;
        }
        $orig_file = $file . '.orig';
        if ($this->fileSystem->exists($orig_file)) {
          $this->fileSystem->remove($orig_file);
        }
        $this->say("The original file is different so create a backup for" . $orig_file);
        $this->fileSystem->rename($file, $orig_file);
      }

      file_put_contents($file, $rendered);
      $this->say("Create a " . $file);
    }
  }

  /**
   * Create directory.
   *
   * @param $dir
   * @param int $mode
   */
  protected function mkDir($dir, $mode = 0) {
    if (!$this->fileSystem->exists($dir)) {
      $this->say("Creating the directory " . $dir);
      $oldmask = umask(0);
      $this->fileSystem->mkdir($dir);
      umask($oldmask);
      switch ($mode) {
        case 1:
          $this->fileSystem->touch($dir . '/.gitkeep');
          break;
        case 2:
          $this->addHtaccess($dir);
          break;
        case 3:
          $this->addHtaccess($dir, TRUE);
          break;
      }
    }
  }

  /**
   * Add htaccess.
   *
   * @param $dir
   * @param bool $private
   */
  protected function addHtaccess($dir, $private = FALSE) {
    $htaccess_path = $dir . '/.htaccess';
    if (!file_exists($htaccess_path) && is_writable($dir)) {
      $htaccess_lines = FileStorage::htaccessLines($private);
      file_put_contents($htaccess_path, $htaccess_lines);
    }
  }

  /**
   * Prepare composer.json for the project.
   */
  protected function prepareComposerJson() {
    $this->taskComposerConfig()->set('name', $this->config['project_name'])->run();
  }

  /**
   * Removes specified lines containing string form the file.
   *
   * @param $file
   * @param $remove_lines array
   */
  protected function removeLines($file, $remove_lines) {
    $lines_array = [];
    $data = file_get_contents($file);
    $lines = explode(PHP_EOL, $data);
    $line_no = 1;
    foreach($lines as $line) {
      $lines_array[$line_no] = $line;
      $line_no++;
    }
    foreach ($remove_lines as $line_num => $line_val) {
      if (strstr($lines_array[$line_num], $line_val)) {
        unset($lines_array[$line_num]);
      }
    }
    file_put_contents($file, implode("\n", $lines_array));
  }

  /**
   * List of files and settings on how to handle them.
   *
   * @param string|array $set_name
   *   The name of the set to be returned.
   *
   * @return array
   *   List of files.
   */
  protected function getFiles($set_name = 'default') {
    $set = [
      'drupal' => [
        [
          'path' => 'drupal/settings.docker.php',
          'dest' => $this->defaultSettingsPath,
        ],
        [
          'path' => 'drupal/settings.prod.php',
          'dest' => $this->defaultSettingsPath,
        ],
        [
          'path' => 'drupal/services.docker.yml',
          'dest' => $this->defaultSettingsPath,
        ],
      ],
      'drush' => [
        [
          'path' => 'drush/default.site.yml',
          'dest' => $this->projectRoot . '/drush/sites',
          'rename' => $this->config['project_machine_name'] . '.site.yml'
        ],
        [
          'path' => 'drush/drush.yml',
          'dest' => $this->projectRoot . '/drush',
        ],
        [
          'path' => 'drush/PolicyCommands.php',
          'dest' => $this->projectRoot . '/drush/Commands',
        ],
        [
          'path' => 'drush/drush-run.sh',
          'dest' => $this->projectRoot . '/drush',
        ],
      ],
      'docker' => [
        [
          'path' => 'docker/docker-compose.yml',
          'dest' => $this->projectRoot,
        ],
        [
          'path' => 'docker/traefik.toml',
          'dest' => $this->projectRoot,
        ],
      ],
      'docker_deploy' => [
        [
          'path' => 'docker/docker-compose.yml',
          'dest' => $this->projectRoot . '/deploy',
          'vars' => [
            'deploy_version' => TRUE,
          ]
        ],
      ],
      'behat' => [
        [
          'path' => 'behat/behat.yml',
          'dest' => $this->projectRoot . '/tests/behat',
        ],
      ],
      'multisite' => [
        [
          'path' => 'multisite/sites.php',
          'dest' => $this->drupalRoot . '/sites',
        ],
      ],
      'deploy' => [
        [
          'path' => 'deploy/.gitlab-ci.yml',
          'dest' => $this->projectRoot,
        ],
        [
          'path' => 'deploy/.rsync-artifact-exclude',
          'dest' => $this->projectRoot . '/deploy',
        ],
        [
          'path' => 'deploy/.rsync-deploy-exclude',
          'dest' => $this->projectRoot . '/deploy',
        ],
        [
          'path' => 'deploy/scripts/artifact.sh',
          'dest' => $this->projectRoot . '/deploy/scripts',
        ],
        [
          'path' => 'deploy/scripts/behat.sh',
          'dest' => $this->projectRoot . '/deploy/scripts',
        ],
        [
          'path' => 'deploy/scripts/deploy.sh',
          'dest' => $this->projectRoot . '/deploy/scripts',
        ],
        [
          'path' => 'deploy/scripts/finalize.sh',
          'dest' => $this->projectRoot . '/deploy/scripts',
        ],
      ],
    ];

    if (is_string($set_name)) {
      if (!isset($set[$set_name]) && $set_name !== 'default') {
        $this->io()->error('The configuration ' . $set_name . ' set is not found!');
        exit;
      }
      if ($set_name == 'default') {
        return array_merge($set['drupal'], $set['drush'], $set['docker']);
      }
      else {
        return $set[$set_name];
      }
    }
    if (is_array($set_name)) {
      $cut = array_intersect_key($set, array_flip($set_name));
      return call_user_func_array('array_merge', $cut);
    }
  }

  /**
   * Return configurations.
   */
  protected function getConfig() {
    if (file_exists($this->projectRoot . '/GoConfig.php')) {
      $config = include $this->projectRoot . '/GoConfig.php';
      return $config;
    }
  }

  /**
   * Set up memcache.
   */
  protected function setUpMemcache() {
    $this->taskComposerRequire()->dependency("drupal/memcache", "^2.0")->run();
    $drush_en_memcache = $this->taskDrushStack()->drush('en memcache')->getCommand();
    $this->commandExec($drush_en_memcache);

    $setting_files = [
      $this->defaultSettingsPath . '/settings.docker.php',
      $this->defaultSettingsPath . '/settings.prod.php',
    ];

    if (!is_writable($this->defaultSettingsPath)) {
      $this->fileSystem->chmod($this->defaultSettingsPath, 0775);
    }

    // Set configurations based on recommendations from Acquia.
    // https://support.acquia.com/hc/en-us/articles/360005313853-Adding-Drupal-8-cache-bins-to-Memcache

    $append = "\n/**\n* Memcached configs.\n*/\n";
    $append .= "\$settings['memcache']['servers'] = ['memcached:11211' => 'default'];\n";
    $append .= "\$settings['memcache']['bins'] = ['default' => 'default'];\n";
    $append .= "\$settings['memcache']['key_prefix'] = '';\n";
    $append .= "\$settings['cache']['bins']['bootstrap'] = 'cache.backend.memcache';\n";
    $append .= "\$settings['cache']['bins']['discovery'] = 'cache.backend.memcache';\n";
    $append .= "\$settings['cache']['bins']['config'] = 'cache.backend.memcache';\n";
    $append .= "\$settings['cache']['default'] = 'cache.backend.memcache';\n";

    foreach ($setting_files as $file_settings) {
      $settings_content = file_get_contents($file_settings);

      if (strpos($settings_content, 'Memcached configs.') === FALSE) {
        $this->fileSystem->appendToFile($file_settings, $append);
        $this->say('Update ' . $file_settings . ' with Memcache configurations.');
      }
    }
  }

  /**
   * Install basic modules.
   */
  protected function installBasicModules() {
    $modules = [
      "drupal/admin_toolbar" => "^1.19",
      "drupal/adminimal_admin_toolbar" => "^1.3",
      "drupal/adminimal_theme" => "1.x-dev",
      "drupal/config_split" => "^1.3",
      "drupal/devel" => "^1.0",
      "drupal/shield" => "^1.2",
    ];

    foreach ($modules as $name => $version) {
      $this->taskComposerRequire()->dependency($name, $version)->run();
    }

    $drush_en_theme = $this->taskDrushStack()->drush('theme:enable adminimal_theme')->getCommand();
    $drush_set_theme = $this->taskDrushStack()->drush('cset system.theme admin adminimal_theme')->getCommand();
    $drush_en_modules = $this->taskDrushStack()->drush('en devel admin_toolbar admin_toolbar_tools adminimal_admin_toolbar config_split')->getCommand();

    $this->commandExec($drush_en_theme);
    $this->commandExec($drush_set_theme);
    $this->commandExec($drush_en_modules);
  }

  /**
   * Remove needless modules.
   */
  protected function removeNeedlessModules() {
    $drush_pmu = $this->taskDrushStack()->drush('pmu color help history quickedit tour search')->getCommand();
    $this->commandExec($drush_pmu);
  }

  /**
   * Overwrite GoConfig.php with the new values.
   */
  protected function updateGoConf() {
    $php = var_export($this->config, true);
    $php = str_replace(["array (", ")"], ["[", "]"], $php);
    $php = preg_replace("/\=\>\s\n(\t|\s)*?\[/", "=> [", $php);
    $php = preg_replace("/[0-9]\s\=\>\s/", "", $php);

    if (empty($this->config['multisite'])) {
      $help_text = "    # Should be in a format 'alias' => 'real production domain'\n";
      $help_text .= "    # Make sure that one of the domain aliases equals the project_machine_name.\n";
      $help_text .= "    #'subdomain' => 'subdomain.com',\n";
      $php = str_replace("'multisite' => [\n", "'multisite' => [\n" . $help_text, $php);
    }

    $php = "<?php\n\nreturn " . $php . ";";
    file_put_contents($this->projectRoot . '/GoConfig.php', $php);
  }
}
