<? include( 'wpdevmate/includes/functions.php' ); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>WPDevMate - WordPress Developer Companion</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="wpdevmate/includes/css/opensans.css" />
<link rel="stylesheet" href="wpdevmate/includes/css/style.min.css" />
<link rel="stylesheet" href="wpdevmate/includes/css/buttons.min.css" />
<link rel="stylesheet" href="wpdevmate/includes/css/bootstrap.min.css" />
<script type="text/javascript" src="wpdevmate/includes/js/jquery.min.js"></script>
</head>
<body class="wp-core-ui">
<div><h1 id="logo"><img src="wpdevmate/includes/images/wpdevmate.png"></h1></div>
<?php
echo 'PHP is working. . . <br/>';
$mysqli = @new mysqli('127.0.0.1:3306', 'root', '');
if ($mysqli->connect_error) {
  echo 'MySQL connection is NOT working with current credentials<br/>';
} else {
  echo 'MySQL connection successful<br/><br/>';
}
?>
<p><a target="_blank" href="/adminer.php?server=localhost:3306&username=root">Open Adminer for MySQL</a><br/>
<a href="/wpdevmate/check.php">Check WordPress Compatability</a><br/>
<a href="/wpdevmate/">Continue to WPDevMate</a><br/></p></body></html>
