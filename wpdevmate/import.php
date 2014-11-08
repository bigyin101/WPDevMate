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
    <title>WPDevMate - Import</title>
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
$wpdm_database_server        = ( isset( $_GET['server'] ) ? $_GET['server'] : null );
$wpdm_database_name          = ( isset( $_GET['database'] ) ? $_GET['database'] : null );
$wpdm_database_user          = ( isset( $_GET['user'] ) ? $_GET['user'] : null );
$wpdm_database_password      = ( isset( $_GET['pass'] ) ? $_GET['pass'] : null );
$wpdm_old_website_name       = ( isset( $_GET['oldsite'] ) ? $_GET['oldsite'] : null );
$wpdm_new_website_prefix     = ( isset( $_GET['prefix'] ) ? $_GET['prefix'] : null );

/***************************************************************************
* import
* 
* import new website and database
* perform search and replace in database to replace old site with new
* on etc.
* htaccess is to complicated to search and replace values, so for now
* rename the existing one and create a new basic wordpress htaccess file.
*
***************************************************************************/

if ( !is_null( $wpdm_website_name ) ) {

    // get variables from existing wp-config
    $wpdm_wpconfig_vars      = wpdm_wordpress_get_wpconfig_info( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php');
    $wpdm_wpc_db_name        = $wpdm_wpconfig_vars['0'];
    $wpdm_wpc_db_user        = $wpdm_wpconfig_vars['1'];
    $wpdm_wpc_db_password    = $wpdm_wpconfig_vars['2'];
    $wpdm_wpc_db_host        = $wpdm_wpconfig_vars['3'];
    $wpdm_wpc_table_prefix   = $wpdm_wpconfig_vars['4'];

    // check the mysql port and append to localhost if NOT default port 3306
    $mysql_port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
    if( $mysql_port != 3306 ) {
        $localhost = WPDM_DB_HOST . ':' . $mysql_port; //3307
    } else {
          $localhost = WPDM_DB_HOST; //3306
    }
    // change the variables in wp-config
    // file, search string
    if ( wpdm_check_string_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', $wpdm_wpc_db_host ) ) {
        wpdm_find_and_replace_in_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', 
                                        "define('DB_HOST', '$wpdm_wpc_db_host');" , "define('DB_HOST', '$wpdm_database_server');");
         wpdm_find_and_replace_in_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', 
                                        "define('DB_HOST', '$wpdm_wpc_db_host');" , "define('DB_HOST', '$localhost');");
    }
    if ( wpdm_check_string_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', $wpdm_wpc_db_user ) ) {
        wpdm_find_and_replace_in_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', "define('DB_USER', '$wpdm_wpc_db_user');" , 
                                        "define('DB_USER', '$wpdm_database_user');");
    }
    // if the password is blank
    if ( $wpdm_wpc_db_password == '' ) {
        $wpdm_wpc_new_pass = $wpdm_database_password;
        wpdm_find_and_replace_in_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', "define('DB_PASSWORD', '');" ,
                                        "define('DB_PASSWORD', '$wpdm_wpc_new_pass');" );
    } else {
        if ( wpdm_check_string_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', $wpdm_wpc_db_password ) ) {
            wpdm_find_and_replace_in_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', "define('DB_PASSWORD', '$wpdm_wpc_db_password');" ,
                                            "define('DB_PASSWORD', '$wpdm_database_password');" );
        }
    }
    if ( wpdm_check_string_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', $wpdm_wpc_db_name ) ) {
        wpdm_find_and_replace_in_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', "define('DB_NAME', '$wpdm_wpc_db_name');" ,
                                        "define('DB_NAME', '$wpdm_database_name');" );
    }
    if ( wpdm_check_string_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', $wpdm_wpc_table_prefix ) ) {
        wpdm_find_and_replace_in_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php', "\$table_prefix  = '$wpdm_wpc_table_prefix';" ,
                                        "\$table_prefix  = '$wpdm_new_website_prefix';" );
    }
    // if htaccess exists, rename it to htaccess_old
    if ( file_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/.htaccess' ) ) {
        rename( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/.htaccess', WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/htaccess_old' );
    } 

    $wpdm_website_path = basename(str_replace( '\\', '/', getcwd() )) . '/' . WPDM_WEBSITES_DIR . '/' . $wpdm_website_name; // wpdevmate/websites/name

    // create a new htaccess file

    $wpdm_htaccess_str = "# BEGIN WordPress\n";
    $wpdm_htaccess_str .= "<IfModule mod_rewrite.c>\n";
    $wpdm_htaccess_str .= "RewriteEngine On\n";
    $wpdm_htaccess_str .= "RewriteBase /\n";
    $wpdm_htaccess_str .= "RewriteRule ^index\.php$ - [L]\n";
    $wpdm_htaccess_str .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
    $wpdm_htaccess_str .= "RewriteCond %{REQUEST_FILENAME} !-d\n";

    if ( $website_name = '' ) {
        $wpdm_htaccess_str .= "RewriteRule . /index.php [L]\n"; 
    } else {
        $wpdm_htaccess_str .= "RewriteRule . /$wpdm_website_path/index.php [L]\n";
    }
    $wpdm_htaccess_str .= "</IfModule>\n";
    $wpdm_htaccess_str .= "# END WordPress\n";

    wpdm_create_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/.htaccess', $wpdm_htaccess_str );


    // find the database.sql file and replace old website name with new one
    if ( file_exists( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/database.sql' ) ) {
        // if the website has already been removed it will skip over this...
        $wpdm_db_tables = array();
        $wpdm_db_tables = wpdm_db_get_tables( $wpdm_database_name, WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS ); 
        wpdm_db_drop_tables( $wpdm_database_name, WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_db_tables ); 

        // replace the old website name with the new one
        $wpdm_sql_file          = WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/database.sql';
        $wpdm_sql_file_contents = file_get_contents( $wpdm_sql_file );
        $wpdm_sql_search_str    = array( 
                                        $wpdm_wpc_table_prefix, 
                                        $wpdm_old_website_name 
                                        ); // existing
        $wpdm_sql_replace_str   = array( 
                                        $wpdm_new_website_prefix,
                                        $wpdm_website_name 
                                        ); // new
        $wpdm_sql_new_contents  = str_replace( $wpdm_sql_search_str, $wpdm_sql_replace_str, $wpdm_sql_file_contents );
        $wpdm_sql_file_handle   = fopen( $wpdm_sql_file, "w" );
        fwrite( $wpdm_sql_file_handle, $wpdm_sql_new_contents );
        fclose( $wpdm_sql_file_handle );
        
        // open database file again and remove the redundant DROP TABLE statements..
        // (to prevent on screen errors on script completion)
        wpdm_delete_line_from_file( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/database.sql', "DROP TABLE " ); // delete the whole line

        try {
            // import the database file
            error_reporting(0);
            wpdm_db_import( $wpdm_database_server, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_database_name, WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/database.sql' );

            // replace siteurl, home etc...
            // The "Home" setting is the address you want people to type in their browser to reach your WordPress blog. #
            // The "Site URL" setting is the address where your WordPress core files reside.
            $wpdm_wp_options     = $wpdm_new_website_prefix . "options";
            $wpdm_wp_posts       = $wpdm_new_website_prefix . "posts";
            $wpdm_wp_postmeta    = $wpdm_new_website_prefix . "postmeta";

            // get the name of the existing siteurl and home
            $wpdm_qry_home          = "SELECT option_value FROM $wpdm_wp_options WHERE option_name = 'home'";
            $wpdm_qry_siteurl       = "SELECT option_value FROM $wpdm_wp_options WHERE option_name = 'siteurl'";

            $wpdm_home_result       = wpdm_db_return_home_siteurl( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_database_name, $wpdm_qry_home );
            $wpdm_siteurl_result    = wpdm_db_return_home_siteurl( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_database_name, $wpdm_qry_siteurl );

            // form new url
            $wpdm_website_new_url   = 'http://' . $_SERVER['HTTP_HOST'] . '/' .  basename( getcwd() ) . '/' . WPDM_WEBSITES_DIR . "/$wpdm_website_name"; // http://127.0.0.1/wpdevmate/websites/name
            $wpdm_website_old_url   = $wpdm_home_result;

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
            wpdm_db_command( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_database_name, $wpdm_query_array );

            // then to finish with, add this in wp-config maybe - just to make sure??
            // http://codex.wordpress.org/Changing_The_Site_URL
            // define('WP_HOME','http://127.0.0.1/wpdm/websites/from-lpm-1.dev');
            // define('WP_SITEURL','http://127.0.0.1/wpdm/websites/from-lpm-1.dev'); 
        } catch ( Exception $e ) {
            echo 'Caught exception: ', $e->getMessage(), '<br/>';
            error_log( $e->getMessage() );
        }
        echo wpdm_close_window( '<p>Database import successfull.</p><br/>' ); // need to login and change 'blogname'

    } else {
        echo '<p>The file <strong>database.sql</strong> doesn\'t exist - unable to import data</p> <br/>';
    }
} else {
    echo '<p><strong>website_name</strong> cannot be empty - please try again.</p><br/>';
}
?>
<div style="text-align: center">
    <small>
        <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
    </small>
</div>
</body>
</html>