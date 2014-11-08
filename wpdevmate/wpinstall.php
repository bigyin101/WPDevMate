<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * This script allows you to install wordpress. You can either
 * download the latest stable or nightly version and install, or
 * you can install directly from the extracted wordpress folder
 * which makes installs really fast.
 * The script also allows you in install from cusom builds. Read
 * the readme file for more information.
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

// check internet connection
if( wpdm_check_internet_connection( 'www.google.com' ) ) {
  $wpdm_connected_to_internet = true;   // connected
} else {
  $wpdm_connected_to_internet = false; // not connected
}
// populate drop down list array with wordpress install options
$wpdm_wp_versions = array(
                          array(
                              'label'       => '--Download Latest version', //0
                              'ddlurl'      => $GLOBALS['WPDM_WORDPRESS_LATEST'],
                              'description' => 'Download the latest stable version. <br/>'
                            ),
                          array(
                              'label'       => '--Download Last nightly', //1
                              'ddlurl'      => $GLOBALS['WPDM_WORDPRESS_NIGHTLY'] , 
                              'description' => 'Download the latest beta version. <br/>'
                            ),
                          array(
                              'label'       => '--Quick Install', //2
                              'ddlurl'      => '/',
                              'description' => ' Install from a previously extracted archive (Quick install).<br/> Just enter a directory name and click install.'
                            ),
                          );
// look for zip files in directory (will use resluts to merge with another array)
$wpdm_wp_zip_files          = wpdm_get_files_in_dir( WPDM_CUSTOM_BUILDS_DIR, '.zip' );
$wpdm_other_wp_versions     = array();

foreach ( $wpdm_wp_zip_files as $wpdm_zip => $wpdm_zip_file_name ) {
  $wpdm_other_wp_versions[] = array(
                                  'label'       => $wpdm_zip_file_name,
                                  'ddlurl'      => WPDM_CUSTOM_BUILDS_DIR . '/' .  $wpdm_zip_file_name,
                                  'description' => "This is a custom WordPress install called: <strong>$wpdm_zip_file_name</strong>.<br/>A new Database will be created to overwrite the existing one."
                                  );
}
// merge the two arrays together to finally populate the installation dropbox
$wpdm_versions_ddl          = array_merge( $wpdm_wp_versions, $wpdm_other_wp_versions );
$wpdm_download_complete     = false;
$wpdm_zip_extract_complete  = false;

/**************************************************************************************
* one-click install
**************************************************************************************/

if ( isset( $_POST['btn_one_click_install'] ) ) {
  if( isset( $_POST['chk_unzip_wp'] ) ) {
    $wpdm_checked = true;
  } else {
    $wpdm_checked = false;
  }
  if( $_POST['folder_name'] == '' ) {
  die( '<p><strong>Directory</strong> cannot be empty. <br/>
      click the BACK button on your browser and enter a Directory to create.</p>' );
  } else {
    $wpdm_folder_name      = $_POST['folder_name'] . WPDM_DIR_EXT;
    header('Location: ' . "one-click.php?website=$wpdm_folder_name&extract=$wpdm_checked");
  }
}
/**************************************************************************************
* on install button click
**************************************************************************************/

if ( isset( $_POST['btn_install'] ) ) {
  /*
<option  value="0" />Latest version</option> -- http://wordpress.org/wordpress-latest.zip
<option  value="1" />Last nightly</option> -- http://wordpress.org/nightly-builds/wordpress-nightly.zip
<option  value="2" />Latest version</option> -- Quick
*/
  // is the directory box empty?
  if( $_POST['folder'] == '' ) {
    die( '<p><strong>Directory</strong> cannot be empty. <br/>
        click the BACK button on your browser and enter a Directory to create.</p>' );
  }
  // is the database box empty?
  if( $_POST['db_name'] == '' ) {
    die( '<p><strong>Database</strong> cannot be empty. <br/>
        click the BACK button on your browser and enter a Database name.</p>' );
  }
  // check if website directory and database already exist
  if( is_dir( WPDM_WEBSITES_DIR . '/' . $_POST['folder'] . WPDM_DIR_EXT ) ) {
    //the website already exists
    die( '<p>A website called <strong>' . $_POST['folder'] . '</strong> already exists.<br/>
     Please change the name of the website or remove it manually.<br/>
     Click the BACK button on your browser and try again.</p>' );
  }
  if( wpdm_db_does_it_exist( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $_POST['db_name'] ) == true ) {
    die( '<p>A database called <strong>' . $_POST['db_name'] . '</strong> already exists.<br/>
     Please change the name of the database or remove it manually.<br/>
     Click the BACK button on your browser and try again.</p>' );
  } else {
    //echo "n";die("Database doesnt exists");
  }
  //download latest version
  if ( isset( $_POST['ddl_versions'] ) && in_array( $_POST['ddl_versions'], array('0') ) ) {
    if( $wpdm_connected_to_internet ) {
      $wpdm_download_complete = wpdm_http_get_file( $wpdm_versions_ddl[$_POST['ddl_versions']]['ddlurl'], WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' );

    } else {
      die( '<p>You need to be connected to the internet to <strong>Download the latest version of WordPress.</strong> <br/>
        Connect to the internet and click the BACK button on your browser to try again. <br/>
        Alternatively, why not try the One-Click install instead...</p>' );
    }
  }
  //download nightly version
  if ( isset( $_POST['ddl_versions'] ) && in_array( $_POST['ddl_versions'], array('1') ) ) {
    if( $wpdm_connected_to_internet ) {
      $wpdm_download_complete = wpdm_http_get_file( $wpdm_versions_ddl[$_POST['ddl_versions']]['ddlurl'], WPDM_WP_DOWNLOAD_DIR . '/wordpress-nightly.zip' );
    } else {
      die( '<p>You need to be connected to the internet to <strong>Download the latest \'Nightly\' version of WordPress.</strong> <br/>
        Connect to the internet and click the BACK button on your browser to try again.  <br/>
        Alternatively, why not try the One-Click install instead...</p>' );
    }
  }
  // quick install
  if ( isset( $_POST['ddl_versions'] ) && in_array( $_POST['ddl_versions'],array('2') ) ) {
    // copy wordpress folder and rename
    //if( !file_exists( WPDM_WP_DOWNLOAD_DIR . '/wordpress' ) ) {
    if( !file_exists( WPDM_WEBSITES_DIR . '/wordpress' ) ) {
      $wpdm_download_complete = false;
      die( '<p>The WordPress directory doesn\'t exist, you may not be connected to the internet <br/>
        or the wordpress directory may not have been pre-extracted. <br/>
        Click the BACK button on your browser to try again.</p>' );
    } else {
      $wpdm_download_complete = true;
    }
  } elseif ( $_POST['ddl_versions'] > '2' ) {
    $wpdm_download_complete = file_exists( $wpdm_versions_ddl[$_POST['ddl_versions']]['ddlurl'] );
  } elseif ( $_POST['ddl_versions'] == '0' ) {
    $wpdm_download_complete = file_exists( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' );
    if( !$wpdm_download_complete ) {
      $wpdm_download_complete = true;
      die( '<p>The file <strong>\'wordpress-latest.zip\'</strong> doesn\'t exist. Connect to the internet and try to download it. <br/>
        Click the BACK button on your browser to try again.</p>' );
    }
  } else {
    //use get latest stable url for remote
    $wpdm_uri                = wpdm_wordpress_get_latest_version_url( $wpdm_versions_ddl[$_POST['ddl_versions']] );
    $wpdm_download_complete  = wpdm_http_get_file( $wpdm_uri, WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip' );
  }
  //unzip archive
  if ( $wpdm_download_complete ) {
    switch ( $_POST['ddl_versions'] ) {
    case '0':
    $wpdm_zip_extract_complete = wpdm_extract_zip_latest( WPDM_WP_DOWNLOAD_DIR . '/wordpress-latest.zip', WPDM_WEBSITES_DIR . '/' . $_POST['folder'] . WPDM_DIR_EXT, false );
      break;
    case '1':
    $wpdm_zip_extract_complete = wpdm_extract_zip_nightly( WPDM_WP_DOWNLOAD_DIR . '/wordpress-nightly.zip', WPDM_WEBSITES_DIR . '/' . $_POST['folder'] . WPDM_DIR_EXT, false );
      break;
     // quick install 
    case '2':
    rename( WPDM_WEBSITES_DIR . '/wordpress', WPDM_WEBSITES_DIR . '/' . $_POST['folder'] . WPDM_DIR_EXT );
    $wpdm_zip_extract_complete = true;
      break;
      // custom builds
    case $_POST['ddl_versions'] > '2':
      // if the folder exists
      $wpdm_zip_extract_complete = wpdm_extract_zip_custombuilds( $wpdm_versions_ddl[$_POST['ddl_versions']]['ddlurl'], WPDM_WEBSITES_DIR . '/' . $_POST['folder'] . WPDM_DIR_EXT );
      break;
    }
  } else {
    echo $_POST['ddl_versions'] . '<p><br/> The File does not exist, Why not try downloading the latest version.</p> <br/>';
    die();
  }
  //create databases
    if ( $_POST['ddl_versions'] > '2' )
    {
      error_reporting(0);
      if( wpdm_db_does_it_exist( WPDM_DB_HOST, WPDM_DB_USER, WPDM_DB_PASS, $_POST['db_name'] ) == false ) {
        wpdm_db_create( WPDM_DB_USER, WPDM_DB_PASS, WPDM_DB_HOST, $_POST['db_name'] );
      } else {
        //echo wpdm_show_messagebox("DATABASE " . $_POST['db_name'] . ".$web_extension DOES EXISTS");
      }
      if( wpdm_db_does_user_exist( $_POST['db_user'],$_POST['db_pass'], DB_HOST ) == false ) {
        error_reporting(0);
        wpdm_db_add_user( WPDM_DB_USER, WPDM_DB_PASS, WPDM_DB_HOST, $_POST['db_user'], $_POST['db_pass'] );
      } else {
        //echo wpdm_show_messagebox("USER " . $_POST['db_user'] . " DOES EXIST");
      }
    } else {
        wpdm_db_create( WPDM_DB_USER, WPDM_DB_PASS, WPDM_DB_HOST, $_POST['db_name'] );
        // add the user first...
        error_reporting(0);
        wpdm_db_add_user( WPDM_DB_USER, WPDM_DB_PASS, WPDM_DB_HOST, $_POST['db_user'], $_POST['db_pass'] ); 
    }
/**************************************************************************************
* virtual hosts
**************************************************************************************/

    // look for string in apache vhosts - NameVirtualHost *
    $wpdm_find_string_nvh         = "NameVirtualHost *"; // this is really important!
    $wpdm_does_string_exist_nvh   = wpdm_check_string_exists( WPDM_APACHE_VHOSTS, $wpdm_find_string_nvh );
    $wpdm_doc_root                = substr( $_SERVER['DOCUMENT_ROOT'], 2 ); // /WPDevMate/webroot

    if( !$wpdm_does_string_exist_nvh ) {
      wpdm_write_to_file( WPDM_APACHE_VHOSTS, "$wpdm_find_string_nvh\n\n" );
    }
    $wpdm_find_string_dr         = "<Directory $wpdm_doc_root>"; 
    $wpdm_does_string_exist_dr   = wpdm_check_string_exists( WPDM_APACHE_VHOSTS, $wpdm_find_string_dr );

    if( !$wpdm_does_string_exist_dr ) {
      wpdm_add_vhost_entry_1( $wpdm_doc_root );
    }
    $wpdm_find_string_dr2        = "DocumentRoot \"$wpdm_doc_root\""; 
    $wpdm_does_string_exist_dr2  = wpdm_check_string_exists( WPDM_APACHE_VHOSTS, $wpdm_find_string_dr2 );
    $wpdm_find_string_sn         = "ServerName localhost"; 
    $wpdm_does_string_exist_sn   = wpdm_check_string_exists( WPDM_APACHE_VHOSTS, $wpdm_find_string_sn );

    // if the root node doesn't exist
    if( !$wpdm_does_string_exist_dr2 && !$wpdm_does_string_exist_sn ) {
      wpdm_add_vhost_entry_2( $wpdm_doc_root );
    }
    $wpdm_website_name          = $_POST['folder'] . WPDM_DIR_EXT;
    $wpdm_document_root_website = $wpdm_doc_root . '/' . basename( getcwd() ). '/' . WPDM_WEBSITES_DIR . '/' . $wpdm_website_name; // /WPDevMate/webroot/wpdevmate/websites/name

    wpdm_add_vhost_entry_3( $wpdm_document_root_website, $wpdm_website_name );

/**************************************************************************************
* after extracted
**************************************************************************************/

  //redirect to installer
  if ( $wpdm_zip_extract_complete ) {
    ?>
    <div style="display: none;">
      <?php wpdm_include_jquery(); ?>
      <?php
      $web      = $_POST['folder'] . WPDM_DIR_EXT;
      $db       = $_POST['db_name'];
      $srv      = $_POST['db_server'];
      $usr      = $_POST['db_user'];
      $pass     = $_POST['db_pass'];
      $prefix   = $_POST['db_prefix'];

      if ( $_POST['ddl_versions'] > '2' ) {
        $wpdm_old_site   = str_replace( '_archive.zip', '', basename( $wpdm_versions_ddl[$_POST['ddl_versions']]['ddlurl'] ) );
        $wpdm_form_action = "import.php?action=import&website=$web&database=$db&server=$srv&user=$usr&pass=$pass&prefix=$prefix&oldsite=$wpdm_old_site";
      } else {
        // go to step 2 of wordpress setup
        $wpdm_form_action = WPDM_WEBSITES_DIR . '/' . $_POST['folder'] . WPDM_DIR_EXT . '/wp-admin/setup-config.php?step=2';
      }
  ?>
<form method="post" action="<?php echo $wpdm_form_action; ?>"  id="frm">
  <p>#Below you should enter your database connection details. If you're not sure about these, contact your host. </p>
  <table class="form-table">
    <tbody>
      <tr>
        <th scope="row"><label for="dbname">Database Name</label></th>
        <td><input name="dbname" id="dbname" type="text" size="25" value="<?php echo $_POST['db_name']; ?>" class="required" ></td>
        <td>#The name of the database you want to run WP in. </td>
      </tr>
      <tr>
        <th scope="row"><label for="uname">User Name</label></th>
        <td><input name="uname" id="uname" type="text" size="25" value="<?php echo $_POST['db_user']; ?>" class="required" ></td>
        <td>Your MySQL username</td>
      </tr>
      <tr>
        <th scope="row"><label for="pwd">Password</label></th>
        <td><input name="pwd" id="pwd" type="text" size="25" value="<?php echo $_POST['db_pass']; ?>" class="required" ></td>
        <td>...and your MySQL password.</td>
      </tr>
      <tr>
        <th scope="row"><label for="dbhost">Database Host</label></th>
        <td><input name="dbhost" id="dbhost" type="text" size="25" value="<?php echo $_POST['db_server']; ?>" class="required" >
        </td>
        <td>You should be able to get this info from your web host, if <code>localhost</code> does not work.</td>
      </tr>
      <tr>
        <th scope="row"><label for="prefix">Table Prefix</label></th>
        <td><input name="prefix" id="prefix" type="text" size="25" value="<?php echo $_POST['db_prefix']; ?>" class="required" ></td>
        <td>If you want to run multiple WordPress installations in a single database, change this.</td>
      </tr>
    </tbody>
  </table>
  <p class="step"><input name="submit" id="sub" type="submit" value="Submit" class="button" ></p>
</form>
    </div>
    <?php
    echo '<script type="text/javascript">
        jQuery(document).ready(function() {
          jQuery("#sub").click();
        });
      </script>';
      exit();
  }
} else {
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>WPDevMate - WordPress Installer</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="includes/css/opensans.css" />
    <link rel="stylesheet" href="includes/css/style.min.css" />
    <link rel="stylesheet" href="includes/css/buttons.min.css" />
    <link rel="stylesheet" href="includes/css/bootstrap.min.css" />
    <script type="text/javascript" src="includes/js/jquery.min.js"></script>
  </head>
  <body class="wp-core-ui">
  <div>
    <h1 id="logo">
      <img src="includes/images/wpdevmate.png">
    </h1>
      <?php if ( wpdm_check_curl_enabled() == false ){ ?>
      <p><strong>cURL</strong> is NOT installed or enabled, and this script needs cURL to work.<br/> Please read the README file for further help on how to fix this.</p>
      <?php die(); } ?>

<form method="post" action="" name="installer_form">
<h1>One-click Install</h1>
<p>Enter the name of the website you want to create and click Go. . .</p>
<table class="form-table">
  <tbody>
    <tr>
      <th scope="row"><label for="chk_unzip_wp">Unzip</label></th>
      <td><label><input name="chk_unzip_wp" id="chk_unzip_wp" type="checkbox"  /> Unzip a newly downloaded version of WordPress.</label></td>
    </tr>
    <tr>
      <th scope="row"><label for="folder_name">Directory to create</label></th>
      <td>
      <input name="folder_name" id="folder_name" type="text" size="25" value="" >
      &nbsp;
      <input name="btn_one_click_install" id="btn_one_click_install" type="submit" value="Go" class="button"  />
      </td>
    </tr>
  </tbody>
</table>
<h1>Custom Install</h1>
<p><strong>OR</strong> Choose an install type, fill in the database details below and click Install WordPress. . .</p>
<table class="form-table">
  <tbody>
    <tr>
      <th scope="row"><label for="ddl_versions">Install type</label></th>
      <td><select name="ddl_versions" id="ddl_versions" >
      <?php
        $wpdm_radios = "";
        foreach ($wpdm_versions_ddl as $key => $arr) {
        $wpdm_radios .=  '<option  value="' . $key .'" />' . $arr['label'] . '</option>';
        }
        $wpdm_radios  = str_replace( 'value="2"','value="2" selected="$arr[\'label\']"', $wpdm_radios );
        echo $wpdm_radios;
      ?>
      </select>
      <input name="installer" type="hidden" value="ins" />
      </td>
    </tr>
    <!--<tr>
    <th height="10" scope="row"><input name="installer" type="hidden" value="ins" /></th>
    </tr>-->
    <tr>
      <th scope="row"><label for="folder">Directory to create</label></th>
      <td>
        <input name="folder" id="folder" type="text" size="25" value="" class="required" >
        <strong><?php echo WPDM_DIR_EXT; ?></strong>
      </td>
    </tr>
    <tr>
      <?php
          // check the mysql port and append to localhost if NOT default port 3306
          $mysql_port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );

          if( $mysql_port != 3306 ) {
              $localhost = WPDM_DB_HOST . ':' . $mysql_port; //3307
          } else {
              $localhost = WPDM_DB_HOST; //3306
          }
      ?>
      <th scope="row"><label for="db_server">Server (localhost)</label></th>
      <td>
        <input name="db_server" id="db_server" type="text" size="25" value="<?php echo $localhost; ?>" class="required" >
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="db_name">Database Name</label></th>
      <td>
        <input name="db_name" id="db_name" type="text" size="25" value="" class="required" >
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="db_user">Database User</label></th>
      <td>
        <input name="db_user" id="db_user" type="text" size="25" value="<?php echo WPDM_DB_USER ?>" class="required" >
        <p>less than 16 chars, no SQL keywords etc</p>
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="db_pass">Database Password</label></th>
      <td>
        <input name="db_pass" id="db_pass" type="password" size="25" value="<?php echo WPDM_DB_PASS ?>" class="required" >
        <p>Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).</p>
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="db_prefix">Database Table Prefix</label></th>
      <td>
        <input name="db_prefix" id="db_prefix" type="text" size="25" value="wp_" class="required" >
        <p>Only numbers, letters, and underscores please!</p>
      </td>
    </tr>
    <tr>
      <th scope="row"><span class="step">
          <input name="btn_install" id="btn_install" type="submit" value="Install WordPress" class="button"  /></span>
      </th>
    </tr>
  </tbody>
</table>
</form>
  <div style="text-align: center"></div>
</div>
  <?php wpdm_include_jquery(); ?>
  <script>
  var descs = [];
  jQuery(document).ready(function()
  {
    <?php
    // versions - the array of the ddl
    foreach ($wpdm_versions_ddl as $key => $arr) {
      echo  'descs[' . $key . '] = "' . $arr['description'] . '<br/>' . '";';
      echo "\n";
    }
    ?>
    // whatever is entered into the 'Directory to create' box
    // is replicated in the 'Database Name' (db_name). Useful for 
    // quick installs.
    jQuery("#folder").change(function()
    {
      jQuery("#db_name").val(jQuery("#folder").val());
    });
    // description (name of the form)
    jQuery("#description").html(descs[jQuery("#ddl_versions").val()]);
    jQuery("#ddl_versions").change(function()
    {
      jQuery("#description").hide('fast');
      jQuery("#description").html(descs[jQuery(this).val()]);
      jQuery("#description").show('2300'); 
    });
  });
  </script>
<div style="text-align: center">
  <small>
    <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
  </small>
</div>
</body>
</html>
  <?php
}
?>