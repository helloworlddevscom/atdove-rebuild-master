<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/default/settings.local.php'.
 */

/**
 * Assertions.
 *
 * The Drupal project primarily uses runtime assertions to enforce the
 * expectations of the API by failing when incorrect calls are made by code
 * under development.
 *
 * @see http://php.net/assert
 * @see https://www.drupal.org/node/2492225
 *
 * If you are using PHP 7.0 it is strongly recommended that you set
 * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
 * or runtime) on development machines and to 0 in production.
 *
 * @see https://wiki.php.net/rfc/expectations
 */
assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

/**
 * Enable local development services.
 */
$local_services = DRUPAL_ROOT . '/sites/development.services.yml';
if (file_exists($local_services)) {
  $settings['container_yamls'][] = $local_services;
}

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Disable the render cache.
 *
 * Note: you should test with the render cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the render cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
$settings['cache']['bins']['render'] = 'cache.backend.null';

/**
 * Disable caching for migrations.
 *
 * Uncomment the code below to only store migrations in memory and not in the
 * database. This makes it easier to develop custom migrations.
 */
# $settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';

/**
 * Disable Internal Page Cache.
 *
 * Note: you should test with Internal Page Cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the page cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Only use this setting once the site has been installed.
 */
$settings['cache']['bins']['page'] = 'cache.backend.null';

/**
 * Disable Dynamic Page Cache.
 *
 * Note: you should test with Dynamic Page Cache enabled, to ensure the correct
 * cacheability metadata is present (and hence the expected behavior). However,
 * in the early stages of development, you may want to disable it.
 */
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

/**
 * Allow test modules and themes to be installed.
 *
 * Drupal ignores test modules and themes by default for performance reasons.
 * During development it can be useful to install test extensions for debugging
 * purposes.
 */
# $settings['extension_discovery_scan_tests'] = TRUE;

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
// $settings['rebuild_access'] = TRUE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Trusted host configuration.
 */
$settings['trusted_host_patterns'] = array(
  '^atdove-rebuild\.lando$',
);

/**
 * Database configuration.
 */
if (!isset($databases))
  $databases = array();

// When using Lando, use Lando settings.
// Prepare a LANDO_INFO constant.
// See: https://jigarius.com/blog/drupal-with-lando
if (isset($_ENV['LANDO_INFO'])) {
  define('LANDO_INFO', json_decode($_ENV['LANDO_INFO'], TRUE));
}
if (defined('LANDO_INFO')) {
  // Configure database for Lando.
  $databases['default']['default'] = [
    'driver' => 'mysql',
    'database' => LANDO_INFO['database']['creds']['database'],
    'username' => LANDO_INFO['database']['creds']['user'],
    'password' => LANDO_INFO['database']['creds']['password'],
    'host' => LANDO_INFO['database']['internal_connection']['host'],
    'port' => LANDO_INFO['database']['internal_connection']['port'],
    'prefix' => '',
  ];
}

// Configure migration database.
$databases['migrate']['default'] = array (
  'database'  => 'drupal7',
  'username'  => 'drupal7',
  'password'  => 'drupal7',
  'host'      => 'database.d7atdove.internal',
  'port'      => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver'    => 'mysql',
);

// Configure migration database.
$databases['legacy']['default'] = array (
  'database'  => 'drupal7',
  'username'  => 'drupal7',
  'password'  => 'drupal7',
  'host'      => 'database.d7atdove.internal',
  'port'      => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver'    => 'mysql',
);

/**
 * Enable or disable config splits to enable and configure development only modules.
 * Note: This line may be modified automatically by Lando by running `lando drupal-config-dev`.
 */
$config['config_split.config_split.config_dev_devel']['status'] = FALSE;
$config['config_split.config_split.config_dev_syslog']['status'] = FALSE;

/**
 * Base URL and scheme
 */
$settings['base_url'] = "https://atdove-rebuild.lando";

$stripeConfig = &$config['stripe.settings']['apikey'];
$stripeConfig['live']['public'] = '';
$stripeConfig['live']['secret'] = '';
$stripeConfig['live']['webhook'] = '';

$stripeConfig['test']['public'] = 'pk_test_51JO2flLYLV63xvRACMg0uPc7dpVKlNkGTL0sU1axbFDfCKWqrCftJDggCP3rK6I9Q5nOa6WgK5sTEA7BuISVQ0YZ003X7Z8odN';
$stripeConfig['test']['secret'] = 'sk_test_51JO2flLYLV63xvRAjMV4mRvZpY5evmFZfB3mcn0FBRkWRvt1okWAf9euv30gfeOEqnXGVta1RDq2tzeeonIqdA61006iZyNcLV';
$stripeConfig['test']['webhook'] = '';

/**
 * Set max depth for Kint to avoid crashing the page.
 */
include_once(DRUPAL_ROOT . './../vendor/kint-php/kint/src/Kint.php');
if (class_exists('Kint')) {
  Kint::$max_depth = 3;
}

/**
 * Temporary file path:
 *
 * A local file system path where temporary files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * If this is not set, the default for the operating system will be used.
 *
 * @see \Drupal\Component\FileSystem\FileSystem::getOsTemporaryDirectory()
 */
$settings['file_temp_path'] = '../tmp';
