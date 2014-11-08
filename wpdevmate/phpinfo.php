<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */

/* 
 * This file is used to display your current server variables
 * and php information. 
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
    <title>WPDevMate - Server Info</title>
    <style type="text/css"> img { float: inherit !important; border: 0px none; } table { width: 100% !important; max-width: 600px; }</style>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="includes/css/opensans.css" />
    <link rel="stylesheet" href="includes/css/style.min.css" />
    <link rel="stylesheet" href="includes/css/buttons.min.css" />
    <link rel="stylesheet" href="includes/css/bootstrap.min.css" />
    <script type="text/javascript" src="includes/js/jquery.min.js"></script>
</head>
<body class="wp-core-ui">
	<div>
      <h1 id="logo"><img src="includes/images/wpdevmate.png" align="center"></h1>
	</div>
<?php

echo "<h2>Server Variables</h2>";
$wpdm_server_vars = array( 'UNIQUE_ID',
							'PHP_SELF', 
							'argv', 
							'argc', 
							'GATEWAY_INTERFACE', 
							'SERVER_ADDR', 
							'SERVER_NAME', 
							'SERVER_SOFTWARE', 
							'SERVER_PROTOCOL', 
							'REQUEST_METHOD', 
							'REQUEST_TIME', 
							'REQUEST_TIME_FLOAT', 
							'QUERY_STRING', 
							'DOCUMENT_ROOT', 
							'HTTP_ACCEPT', 
							'HTTP_ACCEPT_CHARSET', 
							'HTTP_ACCEPT_ENCODING', 
							'HTTP_ACCEPT_LANGUAGE', 
							'HTTP_CONNECTION', 
							'HTTP_HOST', 
							'HTTP_REFERER', 
							'HTTP_USER_AGENT', 
							'HTTPS', 
							'REMOTE_ADDR', 
							'REMOTE_HOST', 
							'REMOTE_PORT', 
							'REMOTE_USER', 
							'REDIRECT_REMOTE_USER',
							'REDIRECT_STATUS', //
							'SCRIPT_FILENAME', 
							'SERVER_ADMIN', 
							'SERVER_PORT', 
							'SERVER_SIGNATURE', 
							'PATH_TRANSLATED', 
							'SCRIPT_NAME', 
							'REQUEST_URI', 
							'PHP_AUTH_DIGEST', 
							'PHP_AUTH_USER', 
							'PHP_AUTH_PW', 
							'AUTH_TYPE', 
							'PATH_INFO', 
							'ORIG_PATH_INFO' ); 

echo '<table align="center" cellpadding="5">'; 

foreach ( $wpdm_server_vars as $arg ) { 
    if ( isset( $_SERVER[$arg] ) ) { 
        echo '<tr><td>' . $arg . '</td><td>' . $_SERVER[$arg] . '</td></tr>'; 
    } else { 
        echo '<tr><td>' . $arg . '</td><td>-</td></tr>'; 
    } 
} 
echo '</table><br/>'; 

phpinfo();
?>
<div style="text-align: center">
	<small>
		<?php echo $GLOBALS['WPDM_FOOTER']; ?> 
	</small>
</div>
</body>
</html>