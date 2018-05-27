<?php

require_once 'vendor/autoload.php';
require_once 'web/core/includes/bootstrap.inc';
require_once 'web/core/includes/install.inc';

use Robo\Tasks;
use Drupal\Component\PhpStorage\FileStorage;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends Tasks {

  use Boedah\Robo\Task\Drush\loadTasks;
  use Droath\RoboDockerCompose\Task\loadTasks;

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

  function __construct() {
    $this->fileSystem = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $this->projectRoot = $drupalFinder->getComposerRoot();
    $this->goRoot = $this->projectRoot . '/go';
    $this->drupalRoot = $drupalFinder->getDrupalRoot();
    $this->defaultSettingsPath = $this->drupalRoot . '/sites/default';
    $this->config = $this->getConfig();
  }

  function test() {
    $this->AddHtaccess();
  }

  function go() {
    $this->getConfig();
    $this->configureProject(TRUE);

    $this->taskDockerComposeUp()
      ->detachedMode()
      ->removeOrphans()
      ->run();

    $this->install();
    $this->prepareComposerJson();

    if ($this->config['include_basic_modules'] == TRUE) {
      $this->installBasicModules();
    }

    if ($this->config['memcached']['enable'] == TRUE) {
      $this->setUpMemcache();
    }

    $this->removeNeedlessModules();
  }

  function conf() {
    $this->configureProject();
  }

  function reconf() {
    $this->recreateConfigFiles();
  }

  function multisite() {
    $this->setUpMultisite();
  }

  function install() {
    $config_dir = Path::makeRelative($this->projectRoot . '/config/default', $this->drupalRoot);

    $drush_install = $this->taskDrushStack()
      ->siteName($this->config['project_name'])
      ->siteMail($this->config['project_machine_name'] . '@example.com')
      ->locale('en')
      ->sitesSubdir('default')
      ->configDir($config_dir)
      ->accountMail('admin@example.com')
      ->accountName('admin')
      ->accountPass('admin')
      ->mysqlDbUrl('drupal:drupal@mariadb:3306/drupal')
      ->disableUpdateStatusModule()
      ->siteInstall('standard')
      ->getCommand();

    $this->dockerComposeExec($drush_install);
    $this->updateSettingsFile();
    $this->AddHtaccess();
  }

  function db_export() {
    $file_name = 'db/' . date('d.m.Y-h.i.s') . '.sql';
    $drush_db_exp = $this->taskDrushStack()
      ->siteAlias('@self')
      ->drush('sql-dump --structure-tables-key=common > ' . $file_name)
      ->getCommand();
    $this->dockerComposeExec($drush_db_exp);
  }

  function db_import() {
    // Drop DB
    $drush_drop = $this->taskDrushStack()->drush('sql-drop')->getCommand();
    $this->dockerComposeExec($drush_drop);

    // Get last db dump.
    $file_name = $this->taskExec('ls db/* | sort -k1 -r| head -1')
      ->interactive(FALSE)
      ->run()->getMessage();
    $file_name = trim(preg_replace('/\s+/', ' ', $file_name));

    // Import DB
    $drush_db_im = $this->taskDrushStack()
      ->siteAlias('@self')
      ->drush('sqlc < ' . $file_name)
      ->getCommand();
    $this->dockerComposeExec($drush_db_im);
  }

  function rebuild() {
    $drush_cc_drush = $this->taskDrushStack()->clearCache('drush')->getCommand();
    $drush_csim = $this->taskDrushStack()->drush('csim')->getCommand();
    $drush_updb = $this->taskDrushStack()->drush('updb')->getCommand();
    $drush_eu = $this->taskDrushStack()->drush('entity-updates')->getCommand();

    $this->dockerComposeExec($drush_cc_drush);
    $this->dockerComposeExec($drush_csim);
    $this->taskComposerInstall()->run();
    $this->dockerComposeExec($drush_updb);
    $this->dockerComposeExec($drush_csim);
    $this->dockerComposeExec($drush_eu);
  }

  function get_db($alias) {
    $drush_create_db = $this->taskDrushStack()->drush('sql-create')->getCommand();
    $drush_sync = $this->taskDrushStack()->drush('sql-sync @' . $alias . ' @self')->getCommand();
    $drush_csim = $this->taskDrushStack()->drush('csim')->getCommand();

    $this->dockerComposeExec($drush_create_db);
    $this->dockerComposeExec($drush_sync);
    $this->dockerComposeExec($drush_csim);
  }

  function get_files($alias) {
    $drush_sync = $this->taskDrushStack()->drush('rsync @' . $alias . ':%files/ @self:%files')->getCommand();
    $this->dockerComposeExec($drush_sync);
  }

  /**
   * Wrapper to execute docker-compose command.
   *
   * @param $command string
   */
  protected function dockerComposeExec($command) {
    $this->taskDockerComposeExecute()->disablePseudoTty()->arg('php')->exec($command)->run();
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
    ];

    foreach ($dirs as $dir => $gitkeep) {
      $this->mkDir($dir, $gitkeep);
    }

    // Add necessary configuration files using prepared templates.
    foreach ($this->getFiles() as $template => $options) {
      $this->makeFileTemplate($template, $options, $ovewrite);
    }

    // Set permissions, see https://wodby.com/stacks/drupal/docs/local/permissions
    exec('setfacl -dR -m u:$(whoami):rwX -m u:82:rwX -m u:100:rX ' . $this->projectRoot);
    exec('setfacl -R -m u:$(whoami):rwX -m u:82:rwX -m u:100:rX ' . $this->projectRoot);
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
        foreach ($dirs as $dir => $gitkeep) {
          $this->mkDir($dir, $gitkeep);
        }

        $file_settings = $this->drupalRoot . '/sites/'. $site_name . '/settings.php';
        $file_def_settings = $this->defaultSettingsPath . '/default.settings.php';

        $settings['config_directories'] = [
          CONFIG_SYNC_DIRECTORY => (object) [
            'value' => Path::makeRelative($this->projectRoot . '/config/' . $site_name . '/default/', $this->drupalRoot),
            'required' => TRUE,
          ],
        ];

        // Create site settings file.
        $this->updateSettingsFile($file_settings, $file_def_settings, $settings);
      }

      // Create sites configuration file.
      $this->makeFileTemplate('sites.php', ['dest' => $this->drupalRoot . '/sites']);
    }
  }

  /**
   * Configure Drupal Project for Docker.
   *
   * @param $file_settings
   * @param $file_def_settings
   * @param $settings
   */
  protected function updateSettingsFile($file_settings = NULL, $file_def_settings = NULL, $settings = NULL) {
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
      $this->fileSystem->chmod($file_settings, 0666);
      $this->say("Create a ' . $file_settings . ' file with mode 666");
    }

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
        drupal_rewrite_settings($settings, $file_settings);
        $this->fileSystem->appendToFile($file_settings, $append);
        $this->fileSystem->chmod($file_settings, 0444);
        $this->fileSystem->chmod($this->defaultSettingsPath, 0555);
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
   * Return configurations.
   *
   * @param $dir
   * @param bool $gitkeep
   */
  protected function mkDir($dir, $gitkeep = FALSE) {
    if (!$this->fileSystem->exists($dir)) {
      $oldmask = umask(0);
      $this->fileSystem->mkdir($dir);
      umask($oldmask);
      if ($gitkeep) {
        $this->fileSystem->touch($dir . '/.gitkeep');
      }
      $this->say("Create a directory " . $dir);
    }
  }

  /**
   * Prepare composer.json for the project.
   */
  protected function prepareComposerJson() {
    $remove_lines = [
      4 => 'homepage',
      5 => 'type',
      6 => 'license',
    ];
    $this->removeLines('composer.json', $remove_lines);
    $this->taskComposerConfig()->set('name', $this->config['project_name'])->run();
    $this->taskComposerConfig()->set('description', 'Drupal 8 project.')->run();
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
    $this->dockerComposeExec($drush_en_memcache);

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
      "drupal/adminimal_theme" => "^1.3",
      "drupal/config_split" => "^1.3",
      "drupal/devel" => "^1.0",
      "drupal/environment_indicator" => "^3.3",
      "drupal/custom_configurations" => "dev-1.x",
      "drupal/session_based_temp_store" => "dev-1.x",
    ];

    foreach ($modules as $name => $version) {
      $this->taskComposerRequire()->dependency($name, $version)->run();
    }

    $drush_en_theme = $this->taskDrushStack()->drush('theme:enable adminimal_theme')->getCommand();
    $drush_set_theme = $this->taskDrushStack()->drush('cset system.theme admin adminimal_theme')->getCommand();
    $drush_en_modules = $this->taskDrushStack()->drush('en devel admin_toolbar admin_toolbar_tools adminimal_admin_toolbar config_split session_based_temp_store')->getCommand();

    $this->dockerComposeExec($drush_en_theme);
    $this->dockerComposeExec($drush_set_theme);
    $this->dockerComposeExec($drush_en_modules);
  }

  /**
   * Remove needless modules.
   */
  protected function removeNeedlessModules() {
    $drush_pmu = $this->taskDrushStack()->drush('pmu color help history quickedit tour update search')->getCommand();
    $this->dockerComposeExec($drush_pmu);
  }

  /**
   * Add htaccess.
   *
   * @param bool $private
   */
  protected function AddHtaccess($private = FALSE) {
    $directory = $this->defaultSettingsPath . '/files/tmp';
    $htaccess_path = $directory . '/.htaccess';
    if (!file_exists($htaccess_path) && is_writable($directory)) {
      $htaccess_lines = FileStorage::htaccessLines($private);
      file_put_contents($htaccess_path, $htaccess_lines);
    }
  }

}
