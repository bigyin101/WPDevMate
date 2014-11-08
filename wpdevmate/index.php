<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * The is the main admin page that displays all the websites that
 * have been installed and that have been backup up etc. 
 * There are also buttons that perform various actions. Read the
 * readme file for more information. 
 *
 * Chris Moore 2014, chrisjamesmoore@gmail.com
 *  
 */

include( 'includes/functions.php' );
// page refreshes:
// every time an action has been performed (website added, removed etc.)
// the result doesn't update on the page unless you manually refresh it.
// this value can be set (in the config file) to a high number to disable.

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
	<meta http-equiv="refresh" content="<?php echo WPDM_PAGE_REFRESH_TIME; ?>" />
	<title>WPDevMate - WordPress Developer Companion</title>
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

<?php

// check if cURL is enabled
if( wpdm_check_curl_enabled() == false) {
	echo '<p><strong>cURL</strong> is NOT installed or enabled, and this script needs cURL to work.<br/> 
	Please read the README file for further help on how to fix this.</p>';
	die();
}
// compatibility checks
// redirect to 'check.php' on first run
/*if( !file_exists( 'hosts' ) ) {
	$wpdm_page_url =  'http://' . $_SERVER['HTTP_HOST'] . '/' . basename( dirname( __FILE__ ) ) . '/check.php'; // str_replace( '\\', '/', getcwd() ) ??
	header( "Location: $wpdm_page_url" );
}*/
// check if the wordpress download directory exists
// check if the wordpress zip file has been downloaded
$wpdm_does_file_exist 				= file_exists( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' );
$wpdm_does_folder_exist				= file_exists( WPDM_WEBSITES_DIR 	. '/wordpress' );
$wpdm_does_hostfile_exist			= file_exists( WPDM_PATH_TO_HOSTS 	. '_wpdm_bak' ); // check if backup host file already exists

$wpdm_msg_connection_with_file 		= '<p style="color:red;">You are not connected to the internet</p>';
$wpdm_msg_connection_without_file 	= '<p style="color:red;">You are not connected to the internet. <br/>
										You need to connect to the internet and download 
										the latest version of WordPress to use WPDevMate.</p>';

// display a confirmation dialog box
$wpdm_confirmation_remove 			= "onclick=\"return confirm('Are you sure want to remove this?')\">";

// add initial directories (from config file) if they dont exist
if( !file_exists( WPDM_WEBSITES_DIR )) {
	wpdm_make_dir( WPDM_WEBSITES_DIR );
}
if( !file_exists( WPDM_CUSTOM_BUILDS_DIR ) ) {
	wpdm_make_dir( WPDM_CUSTOM_BUILDS_DIR );
}
if( !file_exists( WPDM_BACKUP_DIR ) ) {
	wpdm_make_dir( WPDM_BACKUP_DIR );
}
if( !file_exists( WPDM_WP_DOWNLOAD_DIR ) ) {
	wpdm_make_dir( WPDM_WP_DOWNLOAD_DIR );
}
if( !file_exists( 'hosts' ) ) {
	wpdm_create_file( 'hosts', "127.0.0.1   localhost\n\n" );
}

 // add 'NameVirtualHost *' string to apache vhosts file
$wpdm_str_to_find_vhosts = 'NameVirtualHost *';

$wpdm_str_found_vhosts = wpdm_check_string_exists( WPDM_APACHE_VHOSTS, $wpdm_str_to_find_vhosts );

if( !$wpdm_str_found_vhosts ) {
  try{
    wpdm_write_to_file( WPDM_APACHE_VHOSTS, "$wpdm_str_to_find_vhosts\n\n\n" );
  } catch ( Exception $e ) {
    echo 'Caught exception: ',  $e->getMessage(), '<br/>';
    error_log( $e->getMessage() );
  }
}
// search apache vhosts file for root directory string '<Directory /WPDevMate/webroot>'
$wpdm_doc_root = substr( $_SERVER['DOCUMENT_ROOT'], 2 ); 

$wpdm_str_to_find_docroot 	= "<Directory $wpdm_doc_root>";
$wpdm_str_found_docroot 	= wpdm_check_string_exists( WPDM_APACHE_VHOSTS, $wpdm_str_to_find_docroot );

// check if bak hosts file exists
if ( $wpdm_does_hostfile_exist ) {
	$wpdm_copy_hosts_btn =  "style='color:red;'"; 
} else {
	$wpdm_copy_hosts_btn = ""; 
}


if( !$wpdm_str_found_docroot ) {
  // add entry to allow permissions on webroot
  wpdm_add_vhost_entry_1( $wpdm_doc_root );
  // add entry for webroot 
  wpdm_add_vhost_entry_2( $wpdm_doc_root );
}
// check if connected to the internet
if( wpdm_check_internet_connection( 'www.google.com' ) ) {
	$wpdm_connected_to_internet = true; //connected
} else {
	$wpdm_connected_to_internet = false; // not connected
}
// check if 'wordpress' folder exists (if wordpress has been extracted)
if( $wpdm_does_folder_exist ) {
	$wpdm_wordpress_exists_link = ''; // yes
} else {
	$wpdm_wordpress_exists_link = "style='color:red;'"; // no
}
// if connected to the internet, download wordpress if it doesnt already exist
if( $wpdm_connected_to_internet ) {
	$wpdm_connection_message = '';

	if( !$wpdm_does_file_exist ) {
		//ini_set( WPDM_PAGE_REFRESH_TIME, 0 );
		wpdm_http_get_file( $GLOBALS['WPDM_WORDPRESS_LATEST'], WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' );
	} else {
				// sometimes if the internet connection has been turned off whilst downloading, the file can become corrupt
		// so check file size and download again
		if ( 0 == filesize( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' ) ) {
			wpdm_http_get_file( $GLOBALS['WPDM_WORDPRESS_LATEST'], WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' );
		}
	}
	if( !$wpdm_does_folder_exist ) {	
		 wpdm_extract_zip_latest( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' , WPDM_WEBSITES_DIR, false );
	}
} else {
	if( !$wpdm_does_file_exist ) {
		$wpdm_wordpress_exists_link = "style='color:red;'"; // no
		$wpdm_connection_message = $wpdm_msg_connection_without_file;
	} else {
		$wpdm_connection_message = $wpdm_msg_connection_with_file;

		if( !$wpdm_does_folder_exist ) {
			wpdm_extract_zip_latest( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' , WPDM_WEBSITES_DIR, false );
		}
	}
}
//check connection to mysql
if( !wpdm_db_check_connection( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS ) ) {
    echo wpdm_show_messagebox( 'Unable to connect to mysql!' );
}

/**************************************************************************************
* options
**************************************************************************************/
?>
<h1>Admin Options</h1>
<p>
<?php 
// display wordpress version (from wordpress.org)
//echo "<a target='_blank' href='test.php '>TEST</a><br/>";
if( $wpdm_connected_to_internet ) {
	echo '<strong>WordPress version: </strong>' . wpdm_get_wp_version( WPDM_WEBSITES_DIR . '/wordpress/wp-includes/version.php' ) . '<br/>'; // wpdm_wordpress_get_version()
} else {
	echo $wpdm_connection_message;
}
// get the operating system and browser
echo '<strong>Operating Sytem:</strong> ' . wpdm_get_os_version() . '<br/>';
echo '<strong>Browser:</strong> ' . wpdm_get_browser_type();
echo '</p>';

// show help text if set to true in the config file, otherwise hide
if( WPDM_SHOW_HELP ) {
	echo '<small>
	If the <strong>Install WordPress</strong> button link is <font style="color:red;"">red,</font> it means that 
	you either need to connect to the internet and download	the latest version of WordPress, or that the WordPress folder isn\'t 
	fully extracted and ready for the next install.</small><br/>';
}
?>
<br/>	
<small>
	<a <?php echo $wpdm_wordpress_exists_link; ?>target="_blank" class='button' title="Download and install the latest/nightly version of WordPress" href= "wpinstall.php">Install WordPress</a>  
	<a target="_blank" class='button' title="Adminer" href= "/adminer.php?server=<?php echo WPDM_DB_HOST; ?>&username=<?php echo WPDM_DB_USER; ?>">Database Admin</a>  
	<a target="_blank" class='button' title="phpInfo" href= "phpinfo.php">Server Information</a> 
	<a class='button' title="Compatibility check" href= "check.php">Compatibility check</a> 
	<?php
	if ( $wpdm_connected_to_internet ) { ?>
	<a target="_blank" class='button' title="Visit the WordPress Codex" href= "https://developer.wordpress.org/reference/">Codex Code Reference</a><br/>

	<?php } ?>
</small><br/>
<?php
// only show if we're on windows
if( WPDM_SHOW_HELP ) {	
	if( wpdm_get_os_type() == 'Windows' ) {
		echo '<small><strong>Copy hosts file</strong> from WPDevMate to target machine and make a backup of original.<br/> 
		<strong>Cleanup hosts file</strong> removes the backup and restores the original. <br/>
		(If the button is <font style="color:red;"">red,</font> it means that a backup is already present).</small>';
	}
} ?>
<p>	
<?php
// if we're on windows, show the 'add-host' buttons (for adding entry into windows hosts file)
if( wpdm_get_os_type() == 'Windows' ) { ?>
	<a target="_blank" class='button' title="Copy hosts file from WPDevMate to target machine and make backup of original" href= "hostrecords.php?action=copy-hosts">Copy Hosts file</a> 
	<a <?php echo $wpdm_copy_hosts_btn; ?> target="_blank" class='button' title="Remove WPDevMate hosts file from target machine and restore original" href= "hostrecords.php?action=cleanup-hosts">Cleanup Hosts file</a> 
<?php } ?>

<?php  
/**************************************************************************************
* websites
**************************************************************************************/
?>
<h1>WordPress Websites</h1>
<?php
if( WPDM_SHOW_HELP ) {
	echo '<small>
	1. Websites highlighted in <font style="color:red;">red</font> either don\'t have an database associated with them or haven\'t been set up yet.<br/> 
	2. Websites (highlighted in <font style="color:green;"><strong>green</strong></font>) that have virtual host URL\'s, already have entries in the hosts files. <br/>
	3. The <strong>Add-host\'s</strong> button adds entries to WPDevMate\'s own hosts file, apache vHosts and local hosts file. <br/>
	4. The <strong>Remove</strong> button will remove the website from disk, along with its associated database and host entry.</small><br/>'; 
}
// loop through directory and get folders (with extension listed in config file (.local, .dev etc.))
try {
	$wpdm_websites_directory = new DirectoryIterator( WPDM_WEBSITES_DIR );
} catch (Exception $e) {
    echo 'Caught exception: ', $e->getMessage(), "<br/>";
    error_log("Error: Unable to list websites");
}
echo "<br/>";
echo "<table style=\"width:100%\">";
echo '<tbody>';

foreach ( $wpdm_websites_directory as $wpdm_websites_info ) {
	 // only list directories that have extension (as set in config file - .dev / .local etc.)
	 if ( $wpdm_websites_info->isDir() && !$wpdm_websites_info->isDot() && strpos( $wpdm_websites_info, WPDM_DIR_EXT ) ) {
		$wpdm_website_name 	= $wpdm_websites_info->getFilename();
		// Display a confirmation dialog box
		$wpdm_confirmation_remove_website = "onclick=\"return confirm('Are you sure want to remove this Website?')\">";
		// Sometimes, when we remove the website, some files still remain
		// (in particular and empty wp-config-file) and the website name displays red.  
		// To circumvent issues with empty wp-config files we check the file size
		// and if empty we remove the directory.

/*		if ( 0 == filesize( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php' ) )
		{
		    // file is empty
		    wpdm_delete_all( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name ); // just delete the folder
   			wpdm_remove_dir( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name ); // if operation fails, try again...
		}*/
		// get the database name from the wp-config file
		$wpdm_wpconfig_vars = wpdm_wordpress_get_wpconfig_info( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name . '/wp-config.php' );
        $wpdm_wpc_db_name = $wpdm_wpconfig_vars['0'];

		// check if the website has a databse, if not color the website link red
		if( wpdm_db_does_it_exist( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $wpdm_wpc_db_name ) == false ) {
			$wpdm_database_exists_link 		= "style='color:red;'";
			$wpdm_website_underline_text 	= "style='text-decoration:underline;'";
		} else {
			$wpdm_database_exists_link 		= "";
			$wpdm_website_underline_text 	= "";
		}
		// check if the entry for the website exists in both the wpdm hosts file and also apache vhosts
		// if it does, remove the add to hosts link and add a link to the url instead	
		$wpdm_website_url = WPDM_WEBSITES_DIR . '/' . str_replace( '_archive.zip', '', $wpdm_website_name );
		// format urls
		// check if the entry for the website exists in both the wpdm hosts file and vhosts
		// if it does, remove the add to hosts link and add a link to the url instead

        $dir_include = realpath( dirname( __FILE__ ) );
        if( strpos( $dir_include, ':' ) === 1 ) {
            $dir_include = substr( $dir_include, 2 );
        }
        $wpdm_dir = str_replace( '\\', '/', $dir_include );

		//str_replace( '\\', '/', getcwd() ) . '/hosts'; // K:/Dev/VisualStudio/Projects/WPDevMate/WPDevMate/bin/Debug/webroot/localhost/public/wpdevmate
		//$wpdm_dir . '/hosts';							 // /Dev/VisualStudio/Projects/WPDevMate/WPDevMate/bin/Debug/webroot/localhost/public/wpdevmate

		$wpdm_contains_wpdm_host 	= wpdm_check_string_exists_exact_match( $wpdm_dir . '/hosts', $wpdm_website_name );
		$wpdm_contains_vhosts 		= wpdm_check_string_exists( WPDM_APACHE_VHOSTS , $wpdm_website_name );

		// get wordpress version installed
		$wpdm_wp_version = wpdm_get_wp_version( WPDM_WEBSITES_DIR . '/' . $wpdm_website_name  . '/wp-includes/version.php' );

        // get the port number if not default 80
        if( $_SERVER['SERVER_PORT'] !== '80') {
            $wpdm_port_no = ':' . $_SERVER['SERVER_PORT'];
        } else {
            $wpdm_port_no = '';
        }
        $wpdm_website_new_url = $wpdm_website_name . $wpdm_port_no;

		if( $wpdm_contains_wpdm_host && $wpdm_contains_vhosts )	{
			echo "<tr>";
			echo "<td><strong style='color:green;'>$wpdm_website_name</strong></td>"; 
			echo "<td><strong style='color:grey;font-size:13px;'><small>$wpdm_wp_version</small></strong></td>"; 
			echo "<td><a class='button' target='_blank' href='backup.php?website=$wpdm_website_name'>Backup</a></td>";
			echo "<td><a class='button' target='_blank' href='backup.php?website=$wpdm_website_name&includedate=true'>Backup (Date)</a></td>";
			echo "<td><a class='button' target='_blank' href='remove.php?website=$wpdm_website_name' $wpdm_confirmation_remove_website Remove</a></td>";
			echo "<td><a style='text-decoration:underline;' target='_blank' href='http://$wpdm_website_new_url '>http://$wpdm_website_new_url</a></td>";

		} else {
			echo "<tr>";//
			echo "<td><a $wpdm_database_exists_link target='_blank' href='$wpdm_website_url'><strong>$wpdm_website_name</strong></a></td>";
			echo "<td><strong style='color:grey;font-size:13px;'><small>$wpdm_wp_version</small></strong></td>"; 
			echo "<td><a class='button' target='_blank' href='backup.php?website=$wpdm_website_name'>Backup</a></td>";
			echo "<td><a class='button' target='_blank' href='backup.php?website=$wpdm_website_name&includedate=true'>Backup (Date)</a></td>";
			echo "<td><a class='button' target='_blank' href='remove.php?website=$wpdm_website_name' $wpdm_confirmation_remove_website Remove</a></td>";
			echo "<td><a class='button' target='_blank' href='hostrecords.php?action=add-host&website=$wpdm_website_name'>Add-Host</a></td>";
		}	
		echo "</tr>";
	}
}
echo '</tbody></table>';

/**************************************************************************************
* backups
**************************************************************************************/
?>
<h1>WordPress Backups</h1>
<?php
if( WPDM_SHOW_HELP ) {
	echo '<small>
	1, Use the <strong>Upload</strong> button to Upload the archive to a remote server via FTP ready for deployment.<br/>
	2. Use the <strong>Set as Custom</strong> button to copy the archive to the \'custom builds\' directory which can then be used as a
	base install for other projects. </small><br/>';
}
// look for all files called '*_archive.zip' in the directory 
$wpdm_backup_files = ( wpdm_get_files_in_dir( WPDM_BACKUP_DIR, '_archive.zip' ) );
$wpdm_backup_files_array = array();

echo "<br/><table style=\"width:100%\">";
echo '<tbody>';

foreach ( $wpdm_backup_files as $wpdm_b_key => $wpdm_b_value ) {
	echo '<tr>';
	$wpdm_backup_files_array[] = $wpdm_b_value;

	echo "<td><strong>$wpdm_b_value</strong></td>";
	echo "<td><a target='_blank' class='button' href='ftpupload.php?file=$wpdm_b_value'> Upload</a></td>";
	echo "<td><a target='_blank' class='button' href='remove.php?backup=$wpdm_b_value' $wpdm_confirmation_remove Remove</a> </td> ";
	echo "<td><a target='_blank' class='button' href='copy.php?backup=$wpdm_b_value'>Set as Custom</a></td>";
	echo "</tr>";
}
echo '</tbody></table>';

/**************************************************************************************
* custom builds
**************************************************************************************/
?>
<h1>Custom WordPress Builds</h1>
<?php
if( WPDM_SHOW_HELP ) {
	echo '<small>
	Custom WordPress builds (fully customised with plugins etc) populate the dropdown list 
	on the <a target="_blank" href= "wpinstall.php">Install WordPress</a> page.</small><br/>';
}
// look for all files called '*_archive.zip' in the directory 
$wpdm_custom_builds = ( wpdm_get_files_in_dir( WPDM_CUSTOM_BUILDS_DIR, '_archive.zip' ) );
$wpdm_custom_builds_array = array();

echo "<br/><table style=\"width:100%\">";
echo '<tbody>';
foreach ( $wpdm_custom_builds as $wpdm_cb_key => $wpdm_cb_value ) {
	echo '<tr>';

	$wpdm_custom_builds_array[] = $wpdm_cb_value;

	echo "<td><strong>$wpdm_cb_value</strong></td>";
	echo "<td><a target='_blank' class='button' href='remove.php?custombuild=$wpdm_cb_value' $wpdm_confirmation_remove Remove</a></td>";
	echo "</tr>";
}
echo '</tbody></table><br/><h1></h1>';
?>
<small>This Page automatically refreshes every <?php echo WPDM_PAGE_REFRESH_TIME; ?> seconds </small><br/><br/>
</div>
<div style="text-align: center">
	<small>
		<?php echo $GLOBALS['WPDM_FOOTER']; ?> 
	</small>
</div>
</body>
</html>