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
    <title>WPDevMate - Host Records</title>
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

$wpdm_page_action            = ( isset( $_GET['action'] ) ? $_GET['action'] : null );
$wpdm_website_name           = ( isset( $_GET['website'] ) ? $_GET['website'] : null );

/***************************************************************************
* host records
*
* Here we are updating the long urls to make short virtual hostnames
* we do this by doing a search and replace in the database and replacing the
* old url with the new one.
*
* Once we add entries to wpdm and windows host file the website url changes 
* to a virtual hostname. we need to restart the app before this works though
*
***************************************************************************/

switch ( $wpdm_page_action ) {
    
case 'add-host':

    if ( !is_null( $wpdm_website_name ) ) {

        $wpdm_wpconfig_vars             = wpdm_wordpress_get_wpconfig_info( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php');
        $wpdm_wpc_db_name               = $wpdm_wpconfig_vars['0'];
        $wpdm_wpc_table_prefix          = $wpdm_wpconfig_vars['4'];
        $wpdm_wp_options                = $wpdm_wpc_table_prefix . "options";
        $wpdm_wp_posts                  = $wpdm_wpc_table_prefix . "posts";
        $wpdm_wp_postmeta               = $wpdm_wpc_table_prefix . "postmeta";
        // get the name of the existing siteurl and home
        $wpdm_qry_home          = "SELECT option_value FROM $wpdm_wp_options WHERE option_name = 'home'";
        $wpdm_qry_siteurl       = "SELECT option_value FROM $wpdm_wp_options WHERE option_name = 'siteurl'";
        $wpdm_home_result       = wpdm_db_return_home_siteurl( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_name, $wpdm_qry_home );
        $wpdm_siteurl_result    = wpdm_db_return_home_siteurl( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_name, $wpdm_qry_siteurl );
        // form new url
        $wpdm_website_old_url   = $wpdm_home_result;

        // get host name
        if ( strpos( $_SERVER['HTTP_HOST'] ,'localhost') !== false ) {
            $wpdm_http_host = 'localhost';
        } elseif (strpos( $_SERVER['HTTP_HOST'] ,'127.0.0.1') !== false) {
            $wpdm_http_host = '127.0.0.1';
        }
        // get the port number if not default 80
        if( $_SERVER['SERVER_PORT'] !== '80') {
            $wpdm_port_no = ':' . $_SERVER['SERVER_PORT'];
        } else {
            $wpdm_port_no = '';
        }
        if ( strpos( $_SERVER['HTTP_HOST'] ,'localhost') !== false ) {
            $wpdm_http_host = 'localhost';
        } elseif (strpos( $_SERVER['HTTP_HOST'] ,'127.0.0.1') !== false) {
            $wpdm_http_host = '127.0.0.1';
        }
        $wpdm_website_new_url   = "http://" . str_replace( $wpdm_http_host . $wpdm_port_no, $wpdm_website_name . $wpdm_port_no, $_SERVER['HTTP_HOST'] );

        // update options table etc with new data
        $wpdm_qry_change_home           = "UPDATE $wpdm_wp_options 
                                                SET option_value = REPLACE(option_value, '$wpdm_home_result', '$wpdm_website_new_url') 
                                                WHERE option_name = 'home';";
        $wpdm_qry_change_siteurl        = "UPDATE $wpdm_wp_options 
                                                SET option_value = REPLACE(option_value, '$wpdm_siteurl_result', '$wpdm_website_new_url') 
                                                WHERE option_name = 'siteurl';";
        $wpdm_qry_change_guid           = "UPDATE $wpdm_wp_posts 
                                                SET guid = replace(guid, '$wpdm_website_old_url', '$wpdm_website_new_url');";
        $wpdm_qry_change_post_content   = "UPDATE $wpdm_wp_posts 
                                                SET post_content = REPLACE(post_content, '$wpdm_website_old_url', '$wpdm_website_new_url');";
        $wpdm_qry_change_meta           = "UPDATE $wpdm_wp_postmeta 
                                                SET meta_value = REPLACE(meta_value, '$wpdm_website_old_url', '$wpdm_website_new_url');";
        $wpdm_query_array               = array( $wpdm_qry_change_home, 
                                                $wpdm_qry_change_siteurl, 
                                                $wpdm_qry_change_guid, 
                                                $wpdm_qry_change_post_content, 
                                                $wpdm_qry_change_meta 
                                                );
        wpdm_db_command( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_name, $wpdm_query_array );
        // if htaccess exists, rename it to htaccess_old
        // create htaccess file
        if ( file_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/.htaccess' ) ) {
            rename( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/.htaccess', WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/htaccess_old' );
        }
        // create default wordpress htaccess file

        $wpdm_htaccess_str = "# BEGIN WordPress\n";
        $wpdm_htaccess_str .= "<IfModule mod_rewrite.c>\n";
        $wpdm_htaccess_str .= "RewriteEngine On\n";
        $wpdm_htaccess_str .= "RewriteBase /\n";
        $wpdm_htaccess_str .= "RewriteRule ^index\.php$ - [L]\n";
        $wpdm_htaccess_str .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
        $wpdm_htaccess_str .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
        $wpdm_htaccess_str .= "RewriteRule . /index.php [L]\n"; 
        $wpdm_htaccess_str .= "</IfModule>\n";
        $wpdm_htaccess_str .= "# END WordPress\n";

        wpdm_create_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/.htaccess', $wpdm_htaccess_str );

        // might be an issue here when it's not windows...

        $dir_include = realpath( dirname( __FILE__ ) );
        if( strpos( $dir_include, ':' ) === 1 ) {
            $dir_include = substr( $dir_include, 2 );
        }
        $wpdm_dir = str_replace( '\\', '/', $dir_include );



        try{
            // if the windows hosts file exists..
            if( file_exists( WPDM_PATH_TO_HOSTS ) ) {
                wpdm_write_to_file( WPDM_PATH_TO_HOSTS, "127.0.0.1   $wpdm_website_name\n" );  
            } 
            wpdm_write_to_file( $wpdm_dir . '/hosts', "127.0.0.1   $wpdm_website_name\n" );  
        } catch ( Exception $e ) {
            echo 'Caught exception: ',  $e->getMessage(), '<br/>';
            error_log( $e->getMessage() );
        }
    }
    echo wpdm_close_window( '<p>Hosts records have been added for <strong>' . $wpdm_website_name . '</strong>. <br/>
        Please remember to restart Apache </p>' );
    break;

/***************************************************************************
* copy host records - win only
*
* Copy hosts file from WPDevMate to target machine and make backup of original.
* We do this by creating a batch file.
* If not using windows os, this button will not display.
*
***************************************************************************/
case 'copy-hosts':

    if( file_exists( 'copy-hosts.bat' ) ) {
        unlink( 'copy-hosts.bat' );
    }

    if( !file_exists( 'copy-hosts.bat' ) ) {
        // copy the local wpdv hosts file to the target machine (when portable)
        $wpdm_cmd_line =  "copy /Y "        . WPDM_PATH_TO_HOSTS    . " "   . WPDM_PATH_TO_HOSTS . "_wpdm_bak\n";
        $wpdm_cmd_line .= "type hosts >> "  . WPDM_PATH_TO_HOSTS    . "\n";
        $wpdm_cmd_line .= "ipconfig /flushdns\n";

        wpdm_create_file( 'copy-hosts.bat', $wpdm_cmd_line );
    }
    exec( "cmd.exe /c copy-hosts.bat" );
    echo wpdm_close_window( '<p>Hosts file has bee copied to the target machine successfully.</p><br/>' );
    break;

/***************************************************************************
* cleanup hosts - win only
*
* Remove WPDevMate hosts file from the target machine and restores the original.
* If not using windows os, this button will not display.
*
***************************************************************************/
case 'cleanup-hosts':

    if( file_exists( 'cleanup-hosts.bat' ) ) {
        unlink( 'cleanup-hosts.bat' );
    }

    if( !file_exists( 'cleanup-hosts.bat' ) ) {
        $wpdm_cmd_line = "if exist "    . WPDM_PATH_TO_HOSTS . "_wpdm_bak (\n";
        $wpdm_cmd_line .= "del /f /q "  . WPDM_PATH_TO_HOSTS . "\n";
        $wpdm_cmd_line .= "ren "        . WPDM_PATH_TO_HOSTS . "_wpdm_bak hosts\n";
        $wpdm_cmd_line .= "ipconfig /flushdns\n";
        $wpdm_cmd_line .= ")\n";

        wpdm_create_file( 'cleanup-hosts.bat', $wpdm_cmd_line );
    }
    exec( "cmd.exe /c cleanup-hosts.bat" );
    echo wpdm_close_window( '<p>Hosts file has been removed successfully.</p><br/>' );
    break;

default:
    # code...
    break;
}
?>
<div style="text-align: center">
    <small>
        <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
    </small>
</div>
</body>
</html>