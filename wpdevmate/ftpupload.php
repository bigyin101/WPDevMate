<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * This script allows you to upload a previously backed up website archive
 * to a remote server. Once the archive has been uploaded you are able
 * to deploy the installation using the instller script from the WordPress 
 * Duplicator Plugin written by Cory Lamle (http://wordpress.org/plugins/duplicator/)
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

/**************************************************************************************
* variables
**************************************************************************************/

$wpdm_file_name    = ( isset( $_GET['file'] ) ? $_GET['file'] : null );

/***************************************************************************
* ftpupload
*
* Used to upload a backup archive to a remote server
*
***************************************************************************/

// check internet connection
if( !wpdm_check_internet_connection( 'www.google.com' ) ) 
{
   die( '<p>You are not connected to the internet!<br>Connect to the internet and try again.</p>' );
}

if ( !$wpdm_file_name == null ) {
  $wpdm_old_website_url    = 'http://' . $_SERVER['HTTP_HOST'] . '/' . basename(str_replace( '\\', '/', getcwd() )) . '/' . WPDM_WEBSITES_DIR . '/' . str_replace( '_archive.zip', '', $wpdm_file_name ); 
  $wpdm_websitename        = str_replace( '_archive.zip', '', $wpdm_file_name );
  $wpdm_full_path          = WPDM_WEBSITES_DIR . '/' . $wpdm_websitename; 
  $wpdm_destination_url    = '/installer.php';
  $wpdm_display_file_name  = $wpdm_file_name;

  // delete installer file if it already exixts
  if( file_exists( WPDM_BACKUP_DIR . '/installer.php' ) ) {
    unlink( WPDM_BACKUP_DIR . '/installer.php' );
  }
  sleep(2);
  // copy installer to backup directory
  if( !file_exists( WPDM_BACKUP_DIR . '/installer.php' ) ) {
    wpdm_stream_copy( 'includes/installer.php' , WPDM_BACKUP_DIR . '/installer.php' );
  }
  // read info from from wp-config
  $wpdm_wpconfig_vars = wpdm_wordpress_get_wpconfig_info( WPDM_WEBSITES_DIR . '/' . $wpdm_websitename . '/wp-config.php' );
  $wpdm_wpc_table_prefix = $wpdm_wpconfig_vars['4'];
  // we don't know what the destination mysql port number will be, so put this in installer.php
  $wpdm_mysql_port_no = "ini_get( 'mysqli.default_port' )";

  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_mysql_port') ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_mysql_port', $wpdm_mysql_port_no );
  }
  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_db_name') ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_db_name', "''" );
  }
  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_db_user') ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_db_user', "''" );
  }
  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_db_pass') ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_db_pass', "''" );
  }
  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_table_prefix' ) ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_table_prefix', "'$wpdm_wpc_table_prefix'" ); 
  }
  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_old_url') ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_old_url', "'$wpdm_old_website_url'" );
  }
  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_archive_name') ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_archive_name', "'$wpdm_file_name'" );
  }
  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_website_name') ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_website_name', "'$wpdm_websitename'" );
  }
  if ( wpdm_check_string_exists( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_full_path') ) {
    wpdm_find_and_replace_in_file( WPDM_BACKUP_DIR . '/installer.php', '$wpdm_full_path', "'$wpdm_full_path'" );
  }
  $wpdm_files = array( WPDM_BACKUP_DIR . '/' . $wpdm_file_name, WPDM_BACKUP_DIR . '/installer.php' );
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WPDevMate - FTP Uploader</title>
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
if( isset( $_POST['submit'] ) ) {
  $wpdm_details_entered = false;
  // check all details have been entered...
  if( $_POST['ftp_hostname'] == '' || $_POST['ftp_username'] == '' || $_POST['ftp_password'] == '' || $_POST['upload_path'] == '' || $_POST['destination_url'] == '' ) {
    echo wpdm_show_messagebox( 'Please ensure that you enter all details' );
  } else {
    $wpdm_details_entered = true;
  }
  if( $wpdm_details_entered ) {
    if( !is_null( $wpdm_file_name ) ) {
      if( isset( $_POST['chk_unzip_wp'] ) ) {
          $wpdm_sftp_checked = true;
      } else {
          $wpdm_sftp_checked = false;
      }
      if ( !$wpdm_sftp_checked ){
        // check normall FTP...
         // check that the connection details are ok first..
        if ( wpdm_check_ftp_connection( $_POST['ftp_hostname'], $_POST['ftp_port'], $_POST['ftp_username'], $_POST['ftp_password'] ) ) {
          die();
        }
        set_time_limit( 1800 ); // 30 mins

        if( wpdm_upload_files_ftp( $_POST['ftp_hostname'], $_POST['ftp_port'], $_POST['ftp_username'], $_POST['ftp_password'], $_POST['upload_path'], $wpdm_files ) == false ) {
          if ( !$wpdm_file_name == null )  
          {
            // remove installer and wp-config??
            $wpdm_remote_install_url = 'http://' . $_POST['destination_url'] . $wpdm_destination_url;
            echo wpdm_close_window( '<p>Uploading was successful.</p><br/>' );
            echo " <p>Or you can <a href='$wpdm_remote_install_url'>Install WordPress</a> on the remote site now</p><br/><br/>";
          } else {
            $wpdm_remote_install_url = 'http://' . $_POST['destination_url'] . $wpdm_destination_url;
            echo wpdm_close_window( '<p>Uploading was successful.</p> <br/>' );
            echo " <p>Or you can <a href='$wpdm_remote_install_url'>Backup the remote website</a> now</p><br/><br/>";
          }
        } else {
          echo '<p>There was an error with the Upload</p>';
          error_log( '<p>Error: Problem uploading files.</p>' );
        }
      }
      else {
        $dir_include = realpath( dirname( __FILE__ ) );
        // strip drive letter if found
        if( strpos( $dir_include, ':' ) === 1 ) {
            $dir_include = substr( $dir_include, 2 );
        }
        //return str_replace( '\\', '/', $dir_include );
        $wpdm_path_no_drive = str_replace( '\\', '/', $dir_include );
       // connect and upload via sFTP
        $sftp = new Net_SFTP( $_POST['ftp_hostname'] );
        if ( !$sftp->login( $_POST['ftp_username'], $_POST['ftp_password'] ) ) {
          exit( 'Login Failed' );
        } else {
            echo 'Login OK <br/>';
        }
        $start = microtime( true );
        $sftp->put( $_POST['upload_path'] . 'installer.php', $wpdm_path_no_drive . '/' . WPDM_BACKUP_DIR . '/installer.php', NET_SFTP_LOCAL_FILE );
        $sftp->put( $_POST['upload_path'] . $wpdm_file_name, $wpdm_path_no_drive . '/' . WPDM_BACKUP_DIR . '/' . $wpdm_file_name, NET_SFTP_LOCAL_FILE );
        $elapsed = microtime( true ) - $start;
        echo "Operation took $elapsed seconds" . '<br/>';

        if ( !$wpdm_file_name == null ) {
          // remove installer and wp-config??
          $wpdm_remote_install_url = 'http://' . $_POST['destination_url'] . $wpdm_destination_url;
          echo wpdm_close_window( '<p>Uploading was successful.</p><br/>' );
          echo " <p>Or you can <a href='$wpdm_remote_install_url'>Install WordPress</a> on the remote site now</p><br/><br/>";
        } else {
          $wpdm_remote_install_url = 'http://' . $_POST['destination_url'] . $wpdm_destination_url;
          echo wpdm_close_window( '<p>Uploading was successful.</p> <br/>' );
          echo " <p>Or you can <a href='$wpdm_remote_install_url'>Backup the remote website</a> now</p><br/><br/>";
        }
      }
    } 
  }
}                  
?>
<h1>FTP Uploader</h1>
<?php
if( WPDM_SHOW_HELP ) {
  echo '<p>
    <strong>NOTE: </strong> Filename: ** Is set automatically ** <br/>
    sFTP requires that you enter the full path (e.g /home/domain/html/dir/) <br/>
  </p>';
}
?>
  <form method="post" action="" enctype="multipart/form-data">
    <table class="form-table">
      <tbody>
      <tr>
        <th scope="row"><label for="ftp_hostname">Hostname</label></th>
        <td>
          <input name="ftp_hostname" id="ftp_hostname" type="text" size="25" value="" class="required" placeholder="ftp hostname or IP address" >  
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="ftp_port">Port</label>
          </th>
        <td>
          <input name="ftp_port" id="ftp_port" type="text" size="25" value="" class="required" placeholder="usually 21, (22 if using sFTP)" > <label for="chk_unzip_wp">use sFTP?</label>
           <input type="checkbox" name="chk_unzip_wp" />
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="ftp_username">Username</label></th>
        <td>
          <input name="ftp_username" id="ftp_username" type="text" size="25" value="" class="required" placeholder="FTP username" >
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="ftp_password">Password</label></th>
        <td>
          <input name="ftp_password" id="ftp_password" type="password" size="25" value="" class="required" placeholder="FTP password" >
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="uploaded_file">Filename</label></th>
        <td>
          <input name="uploaded_file" id="uploaded_file" size="25" value="<?php echo $wpdm_display_file_name; ?>" disabled > **
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="upload_path">Upload Path</label></th>
        <td>
          <input name="upload_path" id="upload_path"  type="text" size="25" value="" class="required" placeholder="full path (e.g. /public_html/)" >
          (e.g. /public_html/)</td>
      </tr>
      <tr>
        <th scope="row"><label for="destination_url">Destination Url</label></th>
        <td>
          <input name="destination_url" id="destination_url" type="text" size="25" value="" class="required" placeholder="http://www.yourdomain.com">
      <?php echo $wpdm_destination_url; ?></td>
      </tr>
      <tr>
        <th scope="row"><br/><input name="submit" type="submit" value="Upload" class="button" /></th>
      </tr>
      </tbody>
    </table>
  </form>
<div style="text-align: center">
  <small>
    <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
  </small>
</div>
</body>
</html>