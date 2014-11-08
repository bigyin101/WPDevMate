
<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * This file is used to install WordPress with,
 * minimum intervention (one-click install). 
 *
 * Chris Moore 2014, chrisjamesmoore@gmail.com
 *  
 */

include( 'includes/functions.php' );

/**************************************************************************************
* error reporting
**************************************************************************************/

error_reporting( E_ALL ^ E_DEPRECATED );
ini_set( 'log_errors', 1 );
ini_set( 'error_log', 'error.log' );
ini_set( 'max_execution_time', 0 );
ini_set( 'date.timezone', WPDM_TIME_ZONE );

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WPDevMate - One-click Install</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="includes/css/opensans.css" />
    <link rel="stylesheet" href="includes/css/style.min.css" />
    <link rel="stylesheet" href="includes/css/buttons.min.css" />
    <link rel="stylesheet" href="includes/css/bootstrap.min.css" />
    <script type="text/javascript" src="includes/js/jquery.min.js"></script>
</head>
<body class="wp-core-ui">
  <div>
    <h1 id="logo"><img src="includes/images/wpdevmate.png"></h1>
  </div>

<?php

/**************************************************************************************
* variables
**************************************************************************************/

$wpdm_website_name 	= ( isset( $_GET['website'] ) ? $_GET['website'] : null );
$wpdm_extract_zip   = ( isset( $_GET['extract'] ) ? $_GET['extract'] : null );

// check if the wordpress download directory exists
// check if the wordpress zip file has been downloaded
$wpdm_does_file_exist         = file_exists( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' );

if( $wpdm_extract_zip ) {
  if( !$wpdm_does_file_exist ) {
    wpdm_http_get_file( WPDM_WORDPRESS_LATEST, WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' );
    wpdm_extract_zip_latest( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' , WPDM_WEBSITES_DIR, false );
  } else {
    wpdm_extract_zip_latest( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' , WPDM_WEBSITES_DIR, false );
  } 
} else {
  // $wpdm_checked = false;
}

sleep(2);

$wpdm_database_name = basename( $wpdm_website_name, WPDM_DIR_EXT );

// check wordpress directory exists
if( !file_exists( WPDM_WEBSITES_DIR . '/wordpress' ) ) {
  die( '<p>The WordPress directory doesn\'t exist, you may not be connected to the internet <br/>
    or the wordpress directory may not have been pre-extracted. <br/>
    Click the BACK button on your browser to try again. </p>
    <p>Please wait a few more seconds for the WordPress directory to extract, <br/>
    or select the "unzip" method instead.</p>' );
}

sleep(2);

// rename wordpress folder
if( file_exists( $wpdm_website_name ) ) {
  die( '<p>The directory named <strong>' . $wpdm_website_name . '</strong> already exist, <br/>
  Click the BACK button on your browser and enter a new directory name.</p>' );
} else {
  if( !file_exists( WPDM_WEBSITES_DIR . '/wordpress' ) ) {
      $wpdm_wordpress_dir_exists = false;
/*    die( '<p>The WordPress directory doesn\'t exist, you may not be connected to the internet <br/>
      or the wordpress directory may not have been pre-extracted. <br/>
      Click the BACK button on your browser to try again.</p>' );*/
  } else {
    $wpdm_wordpress_dir_exists = true;
  }
}

$wpdm_doc_root                = substr( $_SERVER['DOCUMENT_ROOT'], 2 ); 
$wpdm_document_root_website   = $wpdm_doc_root . '/' . basename( getcwd() ) . '/' . WPDM_WEBSITES_DIR . '/' . $wpdm_website_name; 
$wpdm_wp_url                  = 'http://' . $_SERVER['HTTP_HOST'] . '/' . basename(str_replace( '\\', '/', getcwd() )) . '/' . WPDM_WEBSITES_DIR . '/' . $wpdm_website_name; 
$wpdm_config_sample_location  = $wpdm_document_root_website . '/wp-config-sample.php';
$wpdm_config_file_location    = $wpdm_document_root_website . '/wp-config.php';
$wpdm_string_length           = 64; // number of chars for salt string
$wpdm_full_path               = $wpdm_document_root_website . '/';


if( $wpdm_wordpress_dir_exists ) {
  if ( file_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name ) ) {
    die( '<p>The directory named <strong>' . $wpdm_website_name . '</strong> already exist, <br/>
    Click the BACK button on your browser and enter a new directory name.</p>' );
  } else {
    rename( WPDM_WEBSITES_DIR . '/wordpress', WPDM_WEBSITES_DIR . '/' . $wpdm_website_name );
  }
  // create database
  wpdm_db_create( WPDM_DB_USER, WPDM_DB_PASS, WPDM_DB_HOST, $wpdm_database_name );
  // add site to the vhosts file
  wpdm_add_vhost_entry_3( $wpdm_document_root_website, $wpdm_website_name );
  // copy the wp-config file and rename
/*  if ( !wpdm_stream_copy( $wpdm_config_sample_location, $wpdm_config_file_location ) ) {
      echo 'Failed to copy the wp-config file...<br/>Please refresh your browser and try again.';
  }*/
  if ( !copy( $wpdm_config_sample_location, $wpdm_config_file_location ) ) {
      echo 'Failed to copy the wp-config file...<br/>Please refresh your browser and try again.';
  }
  // check the mysql port and append to localhost if NOT default port 3306
  $mysql_port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );

  if( $mysql_port != 3306 ) {
      $localhost = WPDM_DB_HOST . ':' . $mysql_port; //3307
  } else {
      $localhost = WPDM_DB_HOST; //3306
  }
  // replace values in the wp-config file
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "database_name_here" ,  $wpdm_database_name );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "username_here" ,       WPDM_DB_USER );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "password_here" ,       WPDM_DB_PASS );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "localhost" ,           $localhost ); // WPDM_DB_HOST
  //wpdm_find_and_replace_in_file( $config_file_location,    "'wp_'" ,               "'test_'" );
  // add salt values from random generated string
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "'AUTH_KEY',         'put your unique phrase here'" , "'AUTH_KEY',           " . "'" . wpdm_random_string( $wpdm_string_length ) . "'" );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "'SECURE_AUTH_KEY',  'put your unique phrase here'" , "'SECURE_AUTH_KEY',    " . "'" . wpdm_random_string( $wpdm_string_length ) . "'" );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "'LOGGED_IN_KEY',    'put your unique phrase here'" , "'LOGGED_IN_KEY',      " . "'" . wpdm_random_string( $wpdm_string_length ) . "'" );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "'NONCE_KEY',        'put your unique phrase here'" , "'NONCE_KEY',          " . "'" . wpdm_random_string( $wpdm_string_length ) . "'" );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "'AUTH_SALT',        'put your unique phrase here'" , "'AUTH_SALT',          " . "'" . wpdm_random_string( $wpdm_string_length ) . "'" );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "'SECURE_AUTH_SALT', 'put your unique phrase here'" , "'SECURE_AUTH_SALT',   " . "'" . wpdm_random_string( $wpdm_string_length ) . "'" );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "'LOGGED_IN_SALT',   'put your unique phrase here'" , "'LOGGED_IN_SALT',     " . "'" . wpdm_random_string( $wpdm_string_length ) . "'" );
  wpdm_find_and_replace_in_file( $wpdm_config_file_location, "'NONCE_SALT',       'put your unique phrase here'" , "'NONCE_SALT',         " . "'" . wpdm_random_string( $wpdm_string_length ) . "'" );

  chdir( $wpdm_document_root_website );
  // change the site url
  define( 'WP_SITEURL',    $wpdm_wp_url );
  define( 'WP_INSTALLING', true);

  require_once 'wp-load.php';
  require_once 'wp-admin/includes/upgrade.php';
  require_once 'wp-includes/wp-db.php';
  // install wordpress
  $result = wp_install( WPDM_BLOG_TITLE, WPDM_ADMIN_LOGIN, WPDM_ADMIN_EMAIL, true, null, WPDM_ADMIN_PASSWORD );
}
  echo '<p>The website: <strong>' . $wpdm_website_name . '</strong> has been created.</p><br/>';
  echo '<a target="" class="button" title="website" href="' . home_url()  . '">Goto Website</a>' . "   ";
  echo '<a target="" class="button" title="admin" href="'   . admin_url() . '">Website Admin</a>';
?>
<div style="text-align: center">
  <small>
    <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
  </small>
</div>
</body>
</html>