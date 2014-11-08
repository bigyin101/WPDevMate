<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * This file contains the global variables and
 * settings from the config file.
 *
 * Chris Moore 2014, chrisjamesmoore@gmail.com
 *  
 */

$wpdm_config_settings = parse_ini_file( 'config.ini', 'my-function' ) 
    or die( 'Unable to open the config file (includes/config.ini). <br/>' ); 

/**************************************************************************************
* global variables
**************************************************************************************/

// wpdm settings
define("WPDM_SHOW_HELP",              $wpdm_config_settings['wpdm-settings']['show_help']);
define("WPDM_PAGE_REFRESH_TIME",      $wpdm_config_settings['wpdm-settings']['page_refresh_time']);
define("WPDM_SERVER_TYPE",		      $wpdm_config_settings['wpdm-settings']['server_type']);

// wordpress settings
define("WPDM_BLOG_TITLE",             $wpdm_config_settings['wordpress']['weblog_title']);
define("WPDM_ADMIN_LOGIN",         	  $wpdm_config_settings['wordpress']['user_login']);
define("WPDM_ADMIN_PASSWORD",         $wpdm_config_settings['wordpress']['admin_password']);
define("WPDM_ADMIN_EMAIL",            $wpdm_config_settings['wordpress']['admin_email']);
define("WPDM_BLOG_PUBLIC",            $wpdm_config_settings['wordpress']['blog_public']);
define("WPDM_LOCALE",                 $wpdm_config_settings['wordpress']['locale']);

// database settings
define("WPDM_DB_HOST",                $wpdm_config_settings['database']['db_host']);
define("WPDM_DB_USER",                $wpdm_config_settings['database']['db_user']);
define("WPDM_DB_PASS",                $wpdm_config_settings['database']['db_pass']);
define("WPDM_MYSQL_CONFIG",           $wpdm_config_settings['database']['path_to_mysql_config']);

// host file settings
define("WPDM_APACHE_VHOSTS",          $wpdm_config_settings['host-files']['path_to_vhosts']);
define("WPDM_PATH_TO_HOSTS",          $wpdm_config_settings['host-files']['path_to_hosts']); 

// php settings
define("WPDM_TIME_ZONE",	         $wpdm_config_settings['php']['time_zone']);

// directory settings
define("WPDM_WEBSITES_DIR",          $wpdm_config_settings['directories']['websites_dir']);
define("WPDM_CUSTOM_BUILDS_DIR",     $wpdm_config_settings['directories']['custom_builds_dir']);
define("WPDM_BACKUP_DIR",            $wpdm_config_settings['directories']['backups_dir']);
define("WPDM_WP_DOWNLOAD_DIR",       $wpdm_config_settings['directories']['wordpress_versions']);
define("WPDM_DIR_EXT",               $wpdm_config_settings['directories']['web_extension']);

// wordpress urls
$GLOBALS['WPDM_WORDPRESS_LATEST'] 	= 'http://wordpress.org/latest.zip';
$GLOBALS['WPDM_WORDPRESS_NIGHTLY'] 	= 'http://wordpress.org/nightly-builds/wordpress-latest.zip';

$version = '1.05';
$GLOBALS['WPDM_FOOTER'] = '<a href="http://www.wpdevmate.com">WPDevMate</a> <strong>' . $version . '</strong> 
							| Download from <a href="https://github.com/lucidpixel/WPDevMate">GitHub</a> |
      						<a href="http://www.wpdevmate.com/contact/">Contact us</a>';

?>