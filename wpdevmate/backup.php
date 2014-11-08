<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * This file is used to backing up a wordpress website
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
    <title>WPDevMate - Backup</title>
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

$wpdm_website_name           = ( isset( $_GET['website'] ) ? $_GET['website'] : null );


/***************************************************************************
* backup
*
* Used to backup website directories by creating an sql file called
* 'database.sql' and adding to the directory and then archiving it
* in a zip file. The file is created in a backup folder that is defined
* in the config.ini.php file.
*
***************************************************************************/


if ( !is_null( $wpdm_website_name ) ) { 
    $date_time = '';
    $wpdm_wpconfig_vars      = wpdm_wordpress_get_wpconfig_info( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php');
    $wpdm_wpc_db_name        = $wpdm_wpconfig_vars['0'];
 
    // add the date to the archive [if set]
    if ( isset( $_GET['includedate'] ) ) {
        $date = new DateTime();
        $add_date = $date->format('Y-m-d-H-i-'); //year-month-day-hour-minute - 2014-11-05-02-32
    } else {
        $add_date = '';
    }

    $wpdm_archive_location   = WPDM_BACKUP_DIR . '/' . $add_date . $wpdm_website_name . '_archive.zip';

    if( wpdm_db_does_it_exist( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_name ) == true ) {
        wpdm_db_backup_tables( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_name ); //die();

        //copy database file and put it in wordpress folder
        wpdm_stream_copy( 'database.sql', WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/database.sql' );

        //delete file as we no longer need it
        unlink( 'database.sql' );
        //wpdm_make_dir( WPDM_BACKUP_DIR );
    }

    $wpdm_blacklist_array = array();

    try {
        ExtendedZip::zipTree( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name, $wpdm_archive_location, $wpdm_blacklist_array, ZipArchive::CREATE );
    } catch ( Exception $e ) {
        echo 'Caught exception: ', $e->getMessage(), '<br/>';
        error_log( $e->getMessage() );
    }
    echo wpdm_close_window( '<p>Backup of the <strong>' . $wpdm_website_name . '</strong> website completed.</p><br/>' );

} else {
    echo "<p>You haven't specified a website to backup. <br/> Please run this script from the <a href='index.php'>admin interface.</a></p><br/>";
}
?>
<div style="text-align: center">
    <small>
        <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
    </small>
</div>
</body>
</html>