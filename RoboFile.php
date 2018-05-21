<?php

require_once 'vendor/autoload.php';
require_once 'web/core/includes/bootstrap.inc';
require_once 'web/core/includes/install.inc';

use Robo\Tasks;
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
  protected $drupalRoot;
  /** var string */
  protected $defaultSettingsPath;
  /** var string */
  protected $config;

  function __construct() {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $this->projectRoot = $drupalFinder->getComposerRoot();
    $this->drupalRoot = $drupalFinder->getDrupalRoot();
    $this->defaultSettingsPath = $this->drupalRoot . '/sites/default';
    $this->config = $this->getConfig();
  }

  function configure() {
    $this->configureProject();
  }

  function multisite() {
    $this->setUpMultisite();
  }

  function install() {
    $drush_drop = $this->taskDrushStack()->drush('sql-drop')->getCommand();
    $this->dockerComposeExec($drush_drop);

    $drush_install = $this->taskDrushStack()
      ->siteName($this->config['project_name'])
      ->siteMail($this->config['project_name'] . '@example.com')
      ->locale('en')
      ->sitesSubdir('default')
      ->accountMail('admin@example.com')
      ->accountName('admin')
      ->accountPass('admin')
      ->mysqlDbUrl('drupal:drupal@mariadb:3306/drupal')
      ->disableUpdateStatusModule()
      ->siteInstall('standard')
      ->getCommand();

    $this->dockerComposeExec($drush_install);
    
    //drush config-set system.theme default adminimal_theme
    //drush en admin_toolbar adminimal_admin_toolbar config_split memcache session_based_temp_store
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
   */
  protected function configureProject() {
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

    $file_settings = $this->defaultSettingsPath . '/settings.php';
    $file_def_settings = $this->defaultSettingsPath . '/default.settings.php';

    $settings['config_directories'] = [
      CONFIG_SYNC_DIRECTORY => (object) [
        'value' => Path::makeRelative($this->projectRoot . '/config/default', $this->drupalRoot),
        'required' => TRUE,
      ],
    ];

    // Prepare the settings file for installation
    $this->makeSettingsFile($file_settings, $file_def_settings, $settings);

    // Add necessary configuration files using prepared templates.
    foreach ($this->getFiles() as $template => $options) {
      $this->makeFileTemplate($template, $options);
    }

    //$traefik = new Traefik($options['projectname']);
    //$traefik->update();

    // Set permissions, see https://wodby.com/stacks/drupal/docs/local/permissions
    exec('setfacl -dR -m u:$(whoami):rwX -m u:82:rwX -m u:100:rX ' . $this->projectRoot);
    exec('setfacl -R -m u:$(whoami):rwX -m u:82:rwX -m u:100:rX ' . $this->projectRoot);
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
        $this->makeSettingsFile($file_settings, $file_def_settings, $settings);
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
   *
   * @throws \Exception
   */
  protected function makeSettingsFile($file_settings, $file_def_settings, $settings) {
    $fs = new Filesystem();
    // Prepare the settings file for installation
    if (!$fs->exists($file_settings) && $fs->exists($file_def_settings)) {
      $fs->copy($file_def_settings, $file_settings);
      drupal_rewrite_settings($settings, $file_settings);
      $fs->chmod($file_settings, 0666);
      $this->say("Create a ' . $file_settings . ' file with mode 666");
      // Make sure that settings.docker.php gets called from settings.php.
      $settings_content = file_get_contents($file_settings);
      if (strpos($settings_content, 'settings.docker.php') === FALSE) {
        $relative_path = Path::makeRelative($this->defaultSettingsPath, $this->drupalRoot);
        $settings_content .= "\nif (file_exists(\$app_root . '/" . $relative_path . "/settings.docker.php')) {\n  include \$app_root . '/" . $relative_path . "/settings.docker.php';\n}\n";
        file_put_contents($file_settings, $settings_content);
      }
    }
  }

  /**
   * Create file using prepared templates.
   *
   * @param $template
   * @param $options
   */
  protected function makeFileTemplate($template, $options) {
    $fs = new Filesystem();
    $twig_loader = new \Twig_Loader_Array([]);
    $twig = new \Twig_Environment($twig_loader);

    if (!$fs->exists($options['dest'])) {
      $fs->mkdir($options['dest']);
    }

    $twig_loader->setTemplate($template, $template);
    $filename = $twig->render($template, $this->config);
    $file = $options['dest'] . '/' . $filename;

    if (!$fs->exists($file)) {
      $twig_loader->setTemplate($filename, file_get_contents($this->projectRoot . '/templates/' . $template . '.twig'));
      $rendered = $twig->render($filename, $this->config);

      if (!empty($options['add2yaml']) && isset($this->config[$filename])) {
        $yaml = Yaml::parse($rendered);
        $yaml = array_merge_recursive($yaml, $this->config[$filename]);
        $rendered = Yaml::dump($yaml, 9, 2);
      }

      if ($fs->exists($file)) {
        if (md5_file($file) == md5($rendered)) {
          return;
        }
        $orig_file = $file . '.orig';
        if ($fs->exists($orig_file)) {
          $fs->remove($orig_file);
        }
        $fs->rename($file, $orig_file);
      }
      file_put_contents($file, $rendered);
      $this->say("Create a " . $file);
    }

    if (isset($options['link']) && ($options['link'] != $this->defaultSettingsPath)) {
      $link = $options['link'] . '/' . $filename;
      if (!$fs->exists($link)) {
        $rel = substr($fs->makePathRelative($file, $this->projectRoot . '/' . $link), 3, -1);
        $fs->symlink($rel, $link);
      }
    }

    $fs->chmod($file, 0664);
  }

  /**
   * Return configurations.
   *
   * @param $dir
   * @param bool $gitkeep
   */
  protected function mkDir($dir, $gitkeep = FALSE) {
    $fs = new Filesystem();
    if (!$fs->exists($dir)) {
      $oldmask = umask(0);
      $fs->mkdir($dir);
      umask($oldmask);
      if ($gitkeep) {
        $fs->touch($dir . '/.gitkeep');
      }
      $this->say("Create a directory " . $dir);
    }
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
      'drushrc.php' => [
        'dest' => $this->projectRoot . '/drush',
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
   * Deeply merges arrays. Borrowed from drupal.org/project/core.
   *
   * @param array $arrays
   *   An array of array that will be merged.
   * @param bool $preserve_integer_keys
   *   Whether to preserve integer keys.
   *
   * @return array
   *   The merged array.
   */
  public static function mergeDeepArray(array $arrays, $preserve_integer_keys = FALSE) {
    $result = [];
    foreach ($arrays as $array) {
      foreach ($array as $key => $value) {
        if (is_int($key) && !$preserve_integer_keys) {
          $result[] = $value;
        }
        elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
          $result[$key] = self::mergeDeepArray([$result[$key], $value], $preserve_integer_keys);
        }
        else {
          $result[$key] = $value;
        }
      }
    }
    return $result;
  }

  /**
   * Return configurations.
   */
  protected function getConfig() {
    $config = [
      'project_name' => 'beg',
      'webRoot' => 'web',
      'drush' => [
        'sql' => [
          'tables' => [
            'structure' => [
              'cache',
              'cache_*',
              'history',
              'search_*',
              'sessions',
              'watchdog',
            ],
            'skip' => [
              'migration_*',
            ],
          ],
        ],
        'aliases' => [
          'dev' => [
            'host' => '',
            'user' => '',
            'root' => '',
            'uri' => '',
          ],
          'stage' => [
            'host' => '',
            'user' => '',
            'root' => '',
            'uri' => '',
          ],
          'prod' => [
            'host' => '',
            'user' => '',
            'root' => '',
            'uri' => '',
          ],
        ]
      ],
      'multisite' => [
        'teko' => 'teko.ch',
        'gbssg' => 'gbssg.ch',
        'abbts' => 'abbts.ch',
        'hftm' => 'hftm.ch',
        'zbw' => 'zbw.ch',
        'ibw' => 'ibw.ch',
        'gibb' => 'gibb.ch',
        'stfw' => 'stfw.ch',
        'wiss' => 'wiss.ch',
        'akad' => 'akad.ch',
        'sfb' => 'sfb.ch',
        'bzbuchs' => 'bzbuchs.ch',
      ],
      'php' => [
        'xdebug' => 1,
      ],
      'webserver' => [
        'type' => 'apache',
      ],
      'varnish' => [
        'enable' => 0,
      ],
      'dbbrowser' => [
        'type' => 'adminer',
      ],
      'solr' => [
        'enable' => 0,
      ],
      'redis' => [
        'enable' => 0,
      ],
      'node' => [
        'enable' => 0,
        'key' => '',
        'path' => '',
      ],
      'memcached' => [
        'enable' => 1,
      ],
      'rsyslog' => [
        'enable' => 0,
      ],
      'athenapdf' => [
        'enable' => 0,
        'key' => '',
      ],
      'blackfire' => [
        'enable' => 0,
        'id' => '',
        'token' => '',
      ],
      'webgrind' => [
        'enable' => 0,
      ],
    ];
    return $config;
  }

}
