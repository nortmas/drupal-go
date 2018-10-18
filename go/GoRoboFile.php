<?php

require_once $realDir . '/vendor/autoload.php';
require_once $realDir . '/web/core/includes/bootstrap.inc';
require_once $realDir . '/web/core/includes/install.inc';

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
    $this->removeNeedlessModules();
    $this->yell('Congrats!!! Now you can go here: http://' . $this->config['project_machine_name'] . '.docker.localhost:' . $this->config['port']);
  }

  /**
   * Install Drupal.
   *
   * @param string $profile
   */
  public function install($profile = 'standard') {
    $this->createSettingsFile();
    $this->updateSettingsFile();

    $drush_install = $this->taskDrushStack()
      ->siteName($this->config['project_name'])
      ->siteMail($this->config['project_machine_name'] . '@example.com')
      ->locale('en')
      ->sitesSubdir('default')
      ->accountMail('admin@example.com')
      ->accountName('admin')
      ->accountPass('admin')
      ->mysqlDbUrl('drupal:drupal@mariadb:3306/drupal')
      ->disableUpdateStatusModule()
      ->siteInstall($profile)
      ->getCommand();

    $this->commandExec($drush_install);
  }

  /**
   * Install Drupal.
   * @alias rei
   * @param string $profile
   */
  public function reinstall($profile = 'standard') {
    $drush_drop = $this->taskDrushStack()->drush('sql-drop')->getCommand();
    $this->commandExec($drush_drop);
    $this->install($profile);
    $file_settings = $this->defaultSettingsPath . '/settings.php';
    $this->fileSystem->chmod($this->defaultSettingsPath, 0775);
    $this->fileSystem->chmod($file_settings, 0664);
  }

  /**
   * Create file structure and configure project for Docker.
   */
  public function conf() {
    $this->configureProject();
  }

  /**
   * Recreate configuration files.
   */
  public function reconf() {
    $this->recreateConfigFiles();
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
        $this->projectRoot . '/go/go-conf.php',
        $this->projectRoot . '/drush/sites',
        $this->projectRoot . '/drush/drush.yml',
        $this->projectRoot . '/composer.lock',
        $this->projectRoot . '/phpunit.xml.dist',
        $this->projectRoot . '/private',
        $this->projectRoot . '/config',
        $this->projectRoot . '/db',
        $this->projectRoot . '/test',
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
   * Implement an export of current DB state to the DB folder.
   *
   * @aliases dbe
   */
  public function db_export() {
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
    $drush_csim = $this->taskDrushStack()->drush('csim')->getCommand();
    $drush_updb = $this->taskDrushStack()->drush('updb')->getCommand();
    $drush_eu = $this->taskDrushStack()->drush('entity-updates')->getCommand();
    $composer_install = $this->taskComposerInstall()->getCommand();

    $this->commandExec($drush_cc_drush);
    $this->commandExec($drush_csim);
    $this->commandExec($composer_install);
    $this->commandExec($drush_updb);
    $this->commandExec($drush_csim);
    $this->commandExec($drush_eu);
  }

  /**
   * Import DB from the specified environment.
   *
   * @aliases gdb
   * @param $alias - dev,stage or prod
   */
  public function get_db($alias) {
    $drush_create_db = $this->taskDrushStack()->drush('sql-create')->getCommand();
    $drush_sync = $this->taskDrushStack()->drush('sql-sync @' . $alias . ' @self')->getCommand();
    $drush_csim = $this->taskDrushStack()->drush('csim')->getCommand();

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
    $drush_sync = $this->taskDrushStack()->drush('rsync @' . $alias . ':%files/ @self:%files')->getCommand();
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
      $this->projectRoot . '/private' => 3,
      $this->projectRoot . '/config' => 0,
      $this->projectRoot . '/config/default' => 1,
      $this->projectRoot . '/config/local' => 1,
      $this->projectRoot . '/config/dev' => 1,
      $this->projectRoot . '/config/stage' => 1,
      $this->projectRoot . '/config/prod' => 1,
      $this->projectRoot . '/db' => 1,
      $this->projectRoot . '/test' => 1,
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
    ];

    foreach ($dirs as $dir => $mode) {
      $this->mkDir($dir, $mode);
    }

    // Add necessary configuration files using prepared templates.
    foreach ($this->getFiles() as $template => $options) {
      $this->makeFileTemplate($template, $options, $ovewrite);
    }

    // Set permissions, see https://wodby.com/stacks/drupal/docs/local/permissions
    #exec('setfacl -dR -m u:$(whoami):rwX -m u:82:rwX -m u:100:rX ' . $this->projectRoot);
    #exec('setfacl -R -m u:$(whoami):rwX -m u:82:rwX -m u:100:rX ' . $this->projectRoot);
  }

  /**
   * Recreate config files.
   */
  protected function recreateConfigFiles() {
    $this->io()->caution("This action will overwrite all previously created configs.");
    $save_origin = $this->ask("Do you want to save backups if they differ from the original ones? Y or N");
    $save_origin = strtolower($save_origin) == 'y' ? TRUE : FALSE;
    foreach ($this->getFiles() as $template => $options) {
      $this->makeFileTemplate($template, $options, TRUE, $save_origin);
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
      $this->makeFileTemplate('sites.php', ['dest' => $this->drupalRoot . '/sites']);
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

    // Make sure that settings.docker.php gets called from settings.php.
    if ($this->fileSystem->exists($file_settings)) {
      $settings_content = file_get_contents($file_settings);
      if (strpos($settings_content, 'settings.docker.php') === FALSE) {

        if (!is_writable($this->defaultSettingsPath)) {
          $this->fileSystem->chmod($this->defaultSettingsPath, 0775);
        }

        if (!is_writable($file_settings)) {
          $this->fileSystem->chmod($file_settings, 0664);
        }

        $relative_path = Path::makeRelative($this->defaultSettingsPath, $this->drupalRoot);
        $append = "\nif (file_exists(\$app_root . '/" . $relative_path . "/settings.docker.php')) {\n";
        $append .= "\tinclude \$app_root . '/" . $relative_path . "/settings.docker.php';\n";
        $append .= "}\n";
        $this->fileSystem->appendToFile($file_settings, $append);
        #$this->fileSystem->chmod($file_settings, 0444);
        #$this->fileSystem->chmod($this->defaultSettingsPath, 0555);
      }
    }
  }

  /**
   * Create file using prepared templates.
   *
   * @param $template
   * @param $options
   * @param bool $overwrite
   * @param bool $save_origin
   */
  protected function makeFileTemplate($template, $options, $overwrite = FALSE, $save_origin = FALSE) {
    $twig_loader = new \Twig_Loader_Array([]);
    $twig = new \Twig_Environment($twig_loader);

    if (!$this->fileSystem->exists($options['dest'])) {
      $this->fileSystem->mkdir($options['dest']);
    }

    $twig_loader->setTemplate($template, $template);
    $filename = $twig->render($template, $this->config);
    $file = $options['dest'] . '/' . $filename;

    if (!$this->fileSystem->exists($file) || $overwrite) {
      $twig_loader->setTemplate($filename, file_get_contents($this->goRoot . '/templates/' . $template . '.twig'));
      $rendered = $twig->render($filename, $this->config);

      if (!empty($options['add2yaml']) && isset($this->config[$filename])) {
        $yaml = Yaml::parse($rendered);
        $yaml = array_merge_recursive($yaml, $this->config[$filename]);
        $rendered = Yaml::dump($yaml, 9, 2);
      }

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

    if (isset($options['link']) && ($options['link'] != $this->defaultSettingsPath)) {
      $link = $options['link'] . '/' . $filename;
      if (!$this->fileSystem->exists($link)) {
        $rel = substr($this->fileSystem->makePathRelative($file, $this->projectRoot . '/' . $link), 3, -1);
        $this->fileSystem->symlink($rel, $link);
      }
    }

    $this->fileSystem->chmod($file, 0664);
  }

  /**
   * Create directory.
   *
   * @param $dir
   * @param int $mode
   */
  protected function mkDir($dir, $mode = 0) {
    if (!$this->fileSystem->exists($dir)) {
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
      $this->say("Create a directory " . $dir);
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
   * @return array
   *   List of files.
   */
  protected function getFiles() {
    return [
      'settings.docker.php' => [
        'dest' => $this->defaultSettingsPath,
        'link' => $this->defaultSettingsPath,
      ],
      'services.docker.yml' => [
        'dest' => $this->defaultSettingsPath,
        'link' => $this->defaultSettingsPath,
      ],
      'docker-compose.yml' => [
        'dest' => $this->projectRoot,
        'add2yaml' => TRUE,
      ],
      'default.site.yml' => [
        'dest' => $this->projectRoot . '/drush/sites',
        'add2yaml' => TRUE,
      ],
      'drush.yml' => [
        'dest' => $this->projectRoot . '/drush',
        'add2yaml' => TRUE,
      ],
      'phpunit.xml.dist' => [
        'dest' => $this->projectRoot,
      ],
    ];
  }

  /**
   * Return configurations.
   */
  protected function getConfig() {
    if (!file_exists($this->goRoot . '/go-conf.php') && file_exists($this->goRoot . '/example.go-conf.php')) {
      $this->fileSystem->copy($this->goRoot . '/example.go-conf.php', $this->goRoot . '/go-conf.php');
    }

    if (file_exists($this->goRoot . '/go-conf.php')) {
      $config = include $this->goRoot . '/go-conf.php';
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

    $file_settings = $this->defaultSettingsPath . '/settings.docker.php';
    $settings_content = file_get_contents($file_settings);

    if (strpos($settings_content, 'Memcached configs.') === FALSE) {

      if (!is_writable($this->defaultSettingsPath)) {
        $this->fileSystem->chmod($this->defaultSettingsPath, 0775);
      }

      $append = "\n/**\n* Memcached configs.\n*/\n";
      $append .= "\$settings['memcache']['servers'] = ['127.0.0.1:11211' => 'default'];\n";
      $append .= "\$settings['memcache']['bins'] = ['default' => 'default'];\n";
      $append .= "\$settings['memcache']['key_prefix'] = '';\n";
      $append .= "\$settings['cache']['default'] = 'cache.backend.memcache';\n";
      $append .= "\$settings['cache']['bins']['render'] = 'cache.backend.memcache';\n";

      $this->fileSystem->appendToFile($file_settings, $append);
      $this->fileSystem->chmod($this->defaultSettingsPath, 0555);
      $this->say('Memcached configs have been added.');
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
   * Overwrite go-conf.php with the new values.
   */
  protected function updateGoConf() {
    $php = var_export($this->config, true);
    $php = str_replace(["array (", ")"], ["[", "]"], $php);
    $php = preg_replace("/\=\>\s\n(\t|\s)*?\[/", "=> [", $php);
    $php = preg_replace("/[0-9]\s\=\>\s/", "", $php);

    if (empty($this->config['multisite'])) {
      $help_text = "    # Should be in a format 'alias' => 'real production domain'\n";
      $help_text .= "    #'subdomain' => 'subdomain.com',\n";
      $php = str_replace("'multisite' => [\n", "'multisite' => [\n" . $help_text, $php);
    }

    $php = "<?php\n\nreturn " . $php . ";";
    file_put_contents($this->goRoot . '/go-conf.php', $php);
  }

}
