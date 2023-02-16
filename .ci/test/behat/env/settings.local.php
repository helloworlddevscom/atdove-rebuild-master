<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Configuration overrides for site when running in CircleCI.
 */

$databases['default']['default'] = [
  'database'  => 'circle_test',
  'username'  => 'root',
  'password'  => '',
  'prefix'    => '',
  'host'      => '127.0.0.1',
  'port'      => '',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver'    => 'mysql',
];

$settings['hash_salt'] = 'lorem-ipsum-123';

/**
 * Set Pantheon environment var. This is usually set by
 * Pantheon or the Pantheon Lando recipe. Some tests check this.
 */
$_ENV['PANTHEON_ENVIRONMENT'] = 'lando';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Temporary file path.
 */
$settings['file_temp_path'] = sys_get_temp_dir();
