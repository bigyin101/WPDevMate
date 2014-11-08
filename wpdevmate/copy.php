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
    <title>WPDevMate - Copy</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="includes/css/opensans.css" />
    <link rel="stylesheet" href="includes/css/style.min.css" />
    <link rel="stylesheet" href="includes/css/buttons.min.css" />
    <link rel="stylesheet" href="includes/css/bootstrap.min.css" />
    <script type="text/javascript" src="includes/js/jquery.min.js"></script>
    <meta charset="UTF-8">
</head>
<body class="wp-core-ui">
    <div>
      <h1 id="logo"><img src="includes/images/wpdevmate.png"></h1>
    </div>
    
<?php

/**************************************************************************************
* variables
**************************************************************************************/

$wpdm_backup_file            = ( isset( $_GET['backup'] ) ? $_GET['backup'] : null ); 

/***************************************************************************
* copy
*
* Copies website archives (backups) to the 'custom builds' section / folder
*
***************************************************************************/

    if ( !is_null( $wpdm_backup_file ) ) {
        //copy backups to custom build Folder
        wpdm_stream_copy( WPDM_BACKUP_DIR . '/' . $wpdm_backup_file, WPDM_CUSTOM_BUILDS_DIR ."/$wpdm_backup_file" );
    }
    echo wpdm_close_window( '<p><strong>' . $wpdm_backup_file . '</strong> was succesfully copied to ' . WPDM_CUSTOM_BUILDS_DIR . '</p><br/>' );
?>
<div style="text-align: center">
    <small>
        <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
    </small>
</div>
</body>
</html>