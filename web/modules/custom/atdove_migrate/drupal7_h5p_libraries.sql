-- Database export via SQLPro (https://www.sqlprostudio.com/allapps.html)
-- Exported by philiphenry at 01-03-2022 19:58.
-- WARNING: This file may contain descructive statements such as DROPs.
-- Please ensure that you are running the script at the proper location.


-- BEGIN TABLE h5p_libraries
DROP TABLE IF EXISTS h5p_libraries;
CREATE TABLE `h5p_libraries` (
  `library_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: The id of the library.',
  `machine_name` varchar(127) NOT NULL DEFAULT '' COMMENT 'The library machine name',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'The human readable name of this library',
  `major_version` int(10) unsigned NOT NULL COMMENT 'The version of this library',
  `minor_version` int(10) unsigned NOT NULL COMMENT 'The minor version of this library',
  `patch_version` int(10) unsigned NOT NULL COMMENT 'The patch version of this library',
  `runnable` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'Whether or not this library is executable.',
  `fullscreen` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Indicates if this library can be opened in fullscreen.',
  `embed_types` varchar(255) NOT NULL DEFAULT '' COMMENT 'The allowed embed types for this library as a comma separated list',
  `preloaded_js` text COMMENT 'The preloaded js for this library as a comma separated list',
  `preloaded_css` text COMMENT 'The preloaded css for this library as a comma separated list',
  `drop_library_css` text COMMENT 'List of libraries that should not have CSS included if this library is used. Comma separated list.',
  `semantics` text NOT NULL COMMENT 'The semantics definition in json format',
  `restricted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Restricts the ability to create new content using this library.',
  `tutorial_url` varchar(1000) DEFAULT NULL COMMENT 'URL to a tutorial for this library',
  `has_icon` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Whether or not this library contains an icon.svg',
  `add_to` text COMMENT 'Plugin configuration data',
  `metadata_settings` text COMMENT 'Metadata settings',
  PRIMARY KEY (`library_id`),
  KEY `library` (`machine_name`,`major_version`,`minor_version`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information about what h5p uses what libraries.';

-- Table h5p_libraries contains no data. No inserts have been genrated.
-- Inserting 0 rows into h5p_libraries


-- END TABLE h5p_libraries

