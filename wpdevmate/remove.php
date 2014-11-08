<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * This file is used to perform actions such as backing up,
 * importing or deleting wordpress. 
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
    <title>WPDevMate - Remove</title>
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

$wpdm_backup_file            = ( isset( $_GET['backup'] ) ? $_GET['backup'] : null ); // brandnew01.local_archive.zip
$wpdm_custom_build_file      = ( isset( $_GET['custombuild'] ) ? $_GET['custombuild'] : null );  // brandnew01.local_archive.zip
$wpdm_website_name           = ( isset( $_GET['website'] ) ? $_GET['website'] : null ); // brandnew01.local

/***************************************************************************
* remove
*
* Removes website and ascocciated database
*
***************************************************************************/

// remove the custom build zip file
if ( !is_null( $wpdm_custom_build_file ) ) {
    unlink( WPDM_CUSTOM_BUILDS_DIR . '/' . $wpdm_custom_build_file );
    echo wpdm_close_window( '<p>' . $wpdm_custom_build_file . ' has been removed.</p><br/>' );
}
// remove the backup zip file
if ( !is_null( $wpdm_backup_file ) ) {
    unlink( WPDM_BACKUP_DIR . '/' . $wpdm_backup_file );  // J:/WPDevMate/webroot/wpdevmate  J:/WPDevMate/webroot/wpdevmate/backups
    echo wpdm_close_window( '<p>' . $wpdm_backup_file . ' has been removed.</p><br/>' );
}
// remove the website and database
if ( !is_null( $wpdm_website_name ) ) {
    $wpdm_wpconfig_vars = wpdm_wordpress_get_wpconfig_info( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php');
    $wpdm_wpc_db_name  = $wpdm_wpconfig_vars['0'];
    $wpdm_wpc_db_user  = $wpdm_wpconfig_vars['1'];

    error_reporting(0);
    // check if there is a database ascocciated with the website and delete it
    if ( wpdm_db_does_it_exist( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_name ) == false ) {
        echo "DATABASE $wpdm_website_name DOES NOT EXIST <br/>";
        wpdm_delete_all( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name ); // just delete the folder
        wpdm_remove_dir( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name ); // try again...
        echo wpdm_close_window( '<p>No Database was found, but ' . $wpdm_website_name . ' was removed anyway.</p><br/>' );
        
    } else {
        wpdm_db_drop( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_name ); // drop the database
        // delete user unless user = root
        if ( $wpdm_wpc_db_user == WPDM_DB_USER ) {
        } else {
            wpdm_db_drop_user( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_user ); // drop the user 
        }
        sleep(3); //pause
        wpdm_delete_all( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name ); // just delete the folder
        wpdm_remove_dir( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name ); // if operation fails, try again...
        echo wpdm_close_window( '<p>The website and Database for ' . $wpdm_website_name . ' have been removed.</p><br/>' );
    }
    // remove entry from vhosts file
    // find website name between # tags
    // delete content between tags - #website.dev#
    $wpdm_vhosts_str     = "#$wpdm_website_name#";
    $wpdm_string_to_find = wpdm_get_string_between( file_get_contents( WPDM_APACHE_VHOSTS ), $wpdm_vhosts_str, $wpdm_vhosts_str); // get the string between the #website.dev#
    wpdm_find_and_replace_in_file( WPDM_APACHE_VHOSTS, $wpdm_vhosts_str . $wpdm_string_to_find . $wpdm_vhosts_str, '' );
}
?>
<div style="text-align: center">
    <small>
        <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
    </small>
</div>
</body>
</html>