<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * This script checks that your (LAMP [not Nginx]) server environment is 
 * Compatible and has optimal functionality to work with WordPress. 
 * Please check the WordPress Official Webpage 
 * (http://wordpress.org/about/requirements/) for the latest requirements. 
 * The first five (05) points must be fullfiled in order to run WPDevMate 
 * and for proper WordPress installation.
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
    <title>WPDevMate - Compatibility Test </title>
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
    <div>
        <h1>Compatibility Test</h1>
        <p>This script checks that your (XAMP [not Nginx]) server environment is Compatible and has optimal functionality to work with WordPress. 
        Please check the <a href="http://wordpress.org/about/requirements/" target="blank" rel="nofollow">WordPress Official Webpage for the latest requirements</a>.
        The first five (05) points must be fullfiled in order to run WPDevMate and for proper WordPress installation.</p></br>  
    </div>
<table style="width:100%;">

<?php
echo '<tr><td style="padding-right:10px;" align="right">PHP Version:</td>';
if ( strnatcmp(phpversion(), '5.2.0' ) >= 0 ) {
    echo '<td style="padding-left:10px;">' . phpversion() . ' <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">' . phpversion() . ' <img style="margin-left:10px;" img src="includes/images/no.png" /> <span style="margin-left:10px;color:black">>= 5.2.0 required</span></td></tr>';
}
echo '<tr><td style="padding-right:10px;" align="right">cURL:</td>';

if( function_exists( 'curl_init' ) ) {
    echo '<td style="padding-left:10px;">Enabled <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Not enabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
}

if( function_exists( 'apache_get_modules' ) ) {
    echo '<tr><td style="padding-right:10px;" align="right">Apache mod_rewrite:</td>';
    $wpdm_apache_modules = apache_get_modules();
    if( array_search( 'mod_rewrite', $wpdm_apache_modules ) !== false ) {
        echo '<td style="padding-left:10px;">Enabled <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
    } else {
        echo '<td style="padding-left:10px;">Not enabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
    }
}
echo '<tr><td style="padding-right:10px;" align="right">GD extension:</td>';

if( function_exists( 'gd_info' ) ) {
    echo '<td style="padding-left:10px;">Installed <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Not Installed <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
}
echo '<tr><td style="padding-right:10px;" align="right">Mysql extension:</td>';

if( function_exists( 'mysql_connect' ) ) {
    echo '<td style="padding-left:10px;">Installed <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Not Installed <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>'; 
}
echo '<tr><td style="padding-right:10px;" align="right">php.ini display_errors:</td>';

if( ini_get( 'display_errors' ) ) {
    echo '<td style="padding-left:10px;">disabled <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Enabled (Recommended to be disabled)</td></tr>';
}
echo '<tr><td style="padding-right:10px;" align="right">php.ini memory_limit:</td>';

if( return_bytes( ini_get( 'memory_limit' ) ) >= 268435456 ) {
    echo '<td style="padding-left:10px;">' . ini_get( 'memory_limit' ) . ' <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">' . ini_get( 'memory_limit' ) . ' (Recommended at least 64Mb)</td></tr>';
}
echo '<tr><td style="padding-right:10px;" align="right">php.ini allow_url_fopen:</td>';

if( ini_get( 'allow_url_fopen' ) ) {
    echo '<td style="padding-left:10px;">Enabled <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Disabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
}
echo '<tr><td style="padding-right:10px;" align="right">php.ini safe_mode:</td>';

if( !ini_get( 'safe_mode' ) ) {
    echo '<td style="padding-left:10px;">Disabled <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Enabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
}
echo '<tr><td style="padding-right:10px;" align="right">php.ini short_open_tag:</td>';

if( ini_get( 'short_open_tag' ) ) {
    echo '<td style="padding-left:10px;">Enabled <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Disabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
}
echo '<tr><td style="padding-right:10px;" align="right">php.ini file_uploads:</td>';

if( ini_get( 'file_uploads' ) ) {
    echo '<td style="padding-left:10px;">Enabled <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Disabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
}
echo '<tr><td style="padding-right:10px;" align="right">exiftool:</td>';
$wpdm_exiftool = shell_exec( 'exiftool -ver' );

if( $wpdm_exiftool ) {
    echo '<td style="padding-left:10px;">Installed <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Disabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
} 
$wpdm_zip = shell_exec( 'zip -v' );
echo '<tr><td style="padding-right:10px;" align="right">zip:</td>';

if( $wpdm_zip) {
    echo '<td style="padding-left:10px;">Installed <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Disabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
} 
$wpdm_unzip = shell_exec( 'unzip -v' );
echo '<tr><td style="padding-right:10px;" align="right">unzip:</td>';

if( $wpdm_unzip ) {
    echo '<td style="padding-left:10px;">Installed <img style="margin-left:10px;" img src="includes/images/ok.png" /></td></tr>';
} else {
    echo '<td style="padding-left:10px;">Disabled <img style="margin-left:10px;" img src="includes/images/no.png" /></td></tr>';
}
?>
</table>

<p>You are free to continue with the installation even if there are some missing requirements in your server. However,
if you want a full experience in your WordPress, it is strongly recommended to have all the requirements installed
before proceeding.</p>
    <form method="post" action="index.php" >
        <p class="submit"><input name="submit" type="submit" value="Continue to Admin" class="button" ></p>
    </form>
<?php
function return_bytes( $wpdm_val ) {
    $wpdm_val = trim( $wpdm_val );
    $wpdm_last = strtolower( $wpdm_val[strlen( $wpdm_val )-1] );
    switch( $wpdm_last ) {
        case 'g':
            $wpdm_val *= 1024;
        case 'm':
            $wpdm_val *= 1024;
        case 'k':
            $wpdm_val *= 1024;
    }
    return $wpdm_val;
}
?>
    </div>
<div style="text-align: center">
    <small>
        <?php echo $GLOBALS['WPDM_FOOTER']; ?> 
    </small>
</div>
</body>
</html>
