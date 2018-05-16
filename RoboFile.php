<?php

require 'vendor/autoload.php';

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

  function install() {
    $this->say("Hello I'm going to set up Drupal project for you!");
    $this->createRequiredFiles();
  }

  function configure() {
    $this->configureProject();
  }

  /**
   * Create necessary directories.
   */
  protected function createRequiredFiles() {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $projectRoot = $drupalFinder->getComposerRoot();
    $drupalRoot = $drupalFinder->getDrupalRoot();

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/'. $dir)) {
        $fs->mkdir($drupalRoot . '/'. $dir);
        $fs->touch($drupalRoot . '/'. $dir . '/.gitkeep');
      }
    }

    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') and $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/sync', $drupalRoot),
          'required' => TRUE,
        ],
      ];
      drupal_rewrite_settings($settings, $drupalRoot . '/sites/default/settings.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $this->say("Create a sites/default/settings.php file with chmod 0666");
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $this->say("Create a sites/default/files directory with chmod 0777");
    }
    // Create the custom directory with chmod 0666
    if (!$fs->exists($drupalRoot . '/modules/custom')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/modules/custom', 0666);
      umask($oldmask);
      $this->say("Create a /modules/custom directory with chmod 0666");
    }

    // Create the patches directory with chmod 0666
    if (!$fs->exists($projectRoot . '/patches')) {
      $oldmask = umask(0);
      $fs->mkdir($projectRoot . '/patches', 0666);
      umask($oldmask);
      $this->say("Create a /patches directory with chmod 0666");
    }

    // Create the custom directory with chmod 0666
    if (!$fs->exists($drupalRoot . '/themes/custom')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/themes/custom', 0666);
      umask($oldmask);
      $this->say("Create a /themes/custom directory with chmod 0666");
    }

    // Create the test directory with chmod 0666
    if (!$fs->exists($projectRoot . '/test')) {
      $oldmask = umask(0);
      $fs->mkdir($projectRoot . '/test', 0666);
      umask($oldmask);
      $this->say("Create a /test directory with chmod 0666");
    }

  }

  /**
   * Configure Drupal Project for Docker.
   */
  protected function configureProject() {

    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $projectRoot = $drupalFinder->getComposerRoot();
    $webRoot = $drupalFinder->getDrupalRoot();
    $settingsPath = $webRoot . '/sites/default';

    $files = $this->getFiles($projectRoot, $webRoot, $settingsPath);

    $twig_loader = new \Twig_Loader_Array([]);
    $twig = new \Twig_Environment($twig_loader);

    $options = [
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
      ],
      'drupal' => [
        'version' => '8',
      ],
      'php' => [
        'version' => '7.0',
        'xdebug' => 1,
      ],
      'webserver' => [
        'type' => 'apache',
      ],
      'varnish' => [
        'enable' => 0,
      ],
      'redis' => [
        'version' => '4.0',
      ],
      'dbbrowser' => [
        'type' => 'pma',
      ],
      'solr' => [
        'enable' => 0,
        'version' => '6.6',
      ],
      'node' => [
        'enable' => 0,
        'key' => '',
        'path' => '',
      ],
      'memcached' => [
        'enable' => 0,
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

    // Check if SSH auth sockets are supported.
    $ssh_auth_sock = getenv('SSH_AUTH_SOCK');
    $options['php']['ssh'] = !empty($ssh_auth_sock);
    $options['php']['ssh_auth_sock'] = $ssh_auth_sock;

    foreach ($files as $template => $def) {

      if (!$fs->exists($def['dest'])) {
        $fs->mkdir($def['dest']);
      }

      $twig_loader->setTemplate($template, $template);
      /** @var \Twig_Environment $twig */
      $filename = $twig->render($template, $options);
      $file = $def['dest'] . '/' . $filename;

      if (!$fs->exists($file)) {
        $twig_loader->setTemplate($filename, file_get_contents($projectRoot . '/scripts/templates/' . $template . '.twig'));
        $rendered = $twig->render($filename, $options);

        if (!empty($def['add2yaml']) && isset($options[$filename])) {
          $yaml = Yaml::parse($rendered);
          $yaml = array_merge_recursive($yaml, $options[$filename]);
          $rendered = Yaml::dump($yaml, 9, 2);
        }

        if ($fs->exists($file)) {
          if (md5_file($file) == md5($rendered)) {
            continue;
          }
          $orig_file = $file . '.orig';
          if ($fs->exists($orig_file)) {
            $fs->remove($orig_file);
          }
          $fs->rename($file, $orig_file);
        }
        file_put_contents($file, $rendered);
      }

      if (isset($def['link']) && ($def['link'] != $settingsPath)) {
        $link = $def['link'] . '/' . $filename;
        if (!$fs->exists($link)) {
          $rel = substr($fs->makePathRelative($file, $projectRoot . '/' . $link), 3, -1);
          $fs->symlink($rel, $link);
        }
      }
      $fs->chmod($file, 0664);
    }

    // Make sure that settings.docker.php gets called from settings.php.
    $settingsPhpFile = $settingsPath . '/settings.php';

    if ($fs->exists(($settingsPhpFile))) {
      $settingsPhp = file_get_contents($settingsPhpFile);
      if (strpos($settingsPhp, 'settings.docker.php') === FALSE) {
        $settingsPhp .= "\n\nif (file_exists(__DIR__ . '/settings.docker.php')) {\n  include __DIR__ . '/settings.docker.php';\n}\n";
        file_put_contents($settingsPhpFile, $settingsPhp);
      }
    }

    //$traefik = new Traefik($options['projectname']);
    //$traefik->update();


    /*print "<pre>";
    print_r(getenv('PROJECT_SETTINGS'));
    die;*/

    // Set permissions, see https://wodby.com/stacks/drupal/docs/local/permissions
    exec('setfacl -dR -m u:$(whoami):rwX -m u:82:rwX -m u:100:rX ' . $projectRoot);
    exec('setfacl -R -m u:$(whoami):rwX -m u:82:rwX -m u:100:rX ' . $projectRoot);
  }

  /**
   * List of files and settings on how to handle them.
   *
   * @param string $projectRoot
   *   Name of the project's root directory.
   * @param string $webRoot
   *   Name of the web's root directory.
   * @param string $settingsPath
   *   Name of the settings directory.
   *
   * @return array
   *   List of files.
   */
  protected function getFiles($projectRoot, $webRoot, $settingsPath) {
    return [
      'settings.docker.php' => [
        'dest' => $settingsPath,
        'link' => $settingsPath,
      ],
      'docker-compose.yml' => [
        'dest' => $projectRoot,
        'add2yaml' => TRUE,
      ],
      'drushrc.php' => [
        'dest' => $projectRoot . '/drush',
      ],
      'default.site.yml' => [
        'dest' => $projectRoot . '/drush/sites',
        'add2yaml' => TRUE,
      ],
      'drush.yml' => [
        'dest' => $projectRoot . '/drush',
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


}

