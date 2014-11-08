<?php
/*
  Copyright 2011-12 Cory Lamle  lifeinthegrid.com

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

  SOURCE CONTRIBUTORS:
  Gaurav Aggarwal
  David Coveney of Interconnect IT Ltd
  https://github.com/interconnectit/Search-Replace-DB/
 */

if (file_exists('dtoken.php')) {
    //This is most likely inside the snapshot folder.
    
    //DOWNLOAD ONLY: (Only enable download from within the snapshot directory)
    if (isset($_GET['get']) && isset($_GET['file'])) {
        //Clean the input, strip out anything not alpha-numeric or "_.", so restricts
        //only downloading files in same folder, and removes risk of allowing directory
        //separators in other charsets (vulnerability in older IIS servers), also
        //strips out anything that might cause it to use an alternate stream since
        //that would require :// near the front.
    	$filename = preg_replace('/[^a-zA-Z0-9_.]*/','',$_GET['file']);
    	if (strlen($filename) && file_exists($filename) && (strstr($filename, '_installer.php'))) {
            //Attempt to push the file to the browser
    	    header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=installer.php');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filename));
            //FIXME: We should consider removing all error supression like this
            //as it makes troubleshooting a wild goose chase for times that the
            //script failes on such a line.  The same can and should be accomplished
            //at the server level by turning off displaying errors in PHP.
            @ob_clean();
            @flush();
            if (@readfile($filename) == false) {
                $data = file_get_contents($filename);
                if ($data == false) {
                    die("Unable to read installer file.  The server currently has readfile and file_get_contents disabled on this server.  Please contact your server admin to remove this restriction");
                } else {
                    print $data;
                }
            }
        } else {
            header("HTML/1.1 404 Not Found", true, 404);
            header("Status: 404 Not Found");
        }
    }

	//Prevent Access from rovers or direct browsing in snapshop directory, or when
    //requesting to download a file, should not go past this point.
    exit;
}
?>

<?php if (false) : ?>
    <!DOCTYPE html>
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title>Error: PHP is not running</title>
        </head>
        <body>
            <h2>Error: PHP is not running</h2>
            <p>Duplicator requires that your web server is running PHP. Your server does not have PHP installed, or PHP is turned off.</p>
        </body>
    </html>
<?php endif; ?> 


<?php
/* ==============================================================================================
ADVANCED FEATURES - Allows admins to perform aditional logic on the import.

$GLOBALS['TABLES_SKIP_COLS']
	Add Known column names of tables you don't want the search and replace logic to run on.

$GLOBALS['REPLACE_LIST']
	Add additional search and replace items to step 2 for the serialize engine.  
	Place directly below $GLOBALS['REPLACE_LIST'] variable below your items
	EXAMPLE:
		array_push($GLOBALS['REPLACE_LIST'], array('search' => 'https://oldurl/',  'replace' => 'https://newurl/'));
		array_push($GLOBALS['REPLACE_LIST'], array('search' => 'ftps://oldurl/',   'replace' => 'ftps://newurl/'));
  ================================================================================================= */
// line 412 checkc for default mysql port - $port = empty($port) ? 3306 : $port;
$GLOBALS['WPDM_MYSQL_PORT'] = $wpdm_mysql_port;
$GLOBALS['FW_TABLEPREFIX'] = $wpdm_table_prefix;
$GLOBALS['FW_URL_OLD'] = $wpdm_old_url;
$GLOBALS['FW_URL_NEW'] = '';
$GLOBALS['FW_PACKAGE_NAME'] = $wpdm_archive_name;
$GLOBALS['FW_PACKAGE_NOTES'] = '';
$GLOBALS['FW_SECURE_NAME'] = $wpdm_website_name;
$GLOBALS['FW_DBHOST'] = '';
$GLOBALS['FW_DBHOST'] = empty($GLOBALS['FW_DBHOST']) ? 'localhost' : $GLOBALS['FW_DBHOST'];
$GLOBALS['FW_DBNAME'] = $wpdm_db_name;
$GLOBALS['FW_DBUSER'] = $wpdm_db_user;
$GLOBALS['FW_DBPASS'] = $wpdm_db_pass;
$GLOBALS['FW_SSL_ADMIN'] = 0;
$GLOBALS['FW_SSL_LOGIN'] = 0;
$GLOBALS['FW_CACHE_WP'] = 0;
$GLOBALS['FW_CACHE_PATH'] = 0;
$GLOBALS['FW_BLOGNAME'] = '';
$GLOBALS['FW_WPROOT'] = $wpdm_full_path;
$GLOBALS['FW_DUPLICATOR_VERSION'] = '0.5.8';
$GLOBALS['FW_OPTS_DELETE'] = json_decode('["duplicator_ui_view_state","duplicator_package_active","duplicator_settings"]', true);

//DATABASE SETUP: all time in seconds	
$GLOBALS['DB_MAX_TIME'] = 5000;
$GLOBALS['DB_MAX_PACKETS'] = 268435456;
ini_set('mysql.connect_timeout', '5000');

//PHP SETUP: all time in seconds
ini_set('memory_limit', '5000M');
ini_set("max_execution_time", '5000');
ini_set("max_input_time", '5000');
ini_set('default_socket_timeout', '5000');
@set_time_limit(0);

$GLOBALS['DBCHARSET_DEFAULT'] = 'utf8';
$GLOBALS['DBCOLLATE_DEFAULT'] = 'utf8_general_ci';

//UPDATE TABLE SETTINGS
$GLOBALS['TABLES_SKIP_COLS'] = array('');
$GLOBALS['REPLACE_LIST'] = array();


/* ================================================================================================
  END ADVANCED FEATURES: Do not edit below here.
  =================================================================================================== */

//CONSTANTS
define("DUPLICATOR_INIT", 1); 
define("DUPLICATOR_SSDIR_NAME", 'wp-snapshots');  //This should match DUPLICATOR_SSDIR_NAME in duplicator.php

//SHARED POST PARMS
$_POST['action_step'] = isset($_POST['action_step']) ? $_POST['action_step'] : "1";
$_POST['dbhost']	= isset($_POST['dbhost']) ? trim($_POST['dbhost']) : null;
$_POST['dbuser']	= isset($_POST['dbuser']) ? trim($_POST['dbuser']) : null;
$_POST['dbpass']	= isset($_POST['dbpass']) ? trim($_POST['dbpass']) : null;
$_POST['dbname']	= isset($_POST['dbname']) ? trim($_POST['dbname']) : null;
$_POST['dbcharset'] = isset($_POST['dbcharset'])  ? trim($_POST['dbcharset']) : $GLOBALS['DBCHARSET_DEFAULT'];
$_POST['dbcollate'] = isset($_POST['dbcollate'])  ? trim($_POST['dbcollate']) : $GLOBALS['DBCOLLATE_DEFAULT'];
// $_POST['dbport']	= isset($_POST['dbhost'])	  ? parse_url($_POST['dbhost'], PHP_URL_PORT) : 3306;
// $_POST['dbport']    = (! empty($_POST['dbport'])) ? $_POST['dbport'] : 3306;
$_POST['dbport']	= isset($_POST['dbhost'])	  ? parse_url($_POST['dbhost'], PHP_URL_PORT) : $GLOBALS['WPDM_MYSQL_PORT'];
$_POST['dbport']    = (! empty($_POST['dbport'])) ? $_POST['dbport'] : $GLOBALS['WPDM_MYSQL_PORT'];

//GLOBALS
$GLOBALS["SQL_FILE_NAME"] = "installer-data.sql";
$GLOBALS["LOG_FILE_NAME"] = "installer-log.txt";
$GLOBALS['SEPERATOR1'] = str_repeat("********", 10);
$GLOBALS['LOGGING'] = isset($_POST['logging']) ? $_POST['logging'] : 1;
$GLOBALS['CURRENT_ROOT_PATH'] = dirname(__FILE__);
$GLOBALS['CHOWN_ROOT_PATH'] = @chmod("{$GLOBALS['CURRENT_ROOT_PATH']}", 0755);
$GLOBALS['CHOWN_LOG_PATH'] = @chmod("{$GLOBALS['CURRENT_ROOT_PATH']}/{$GLOBALS['LOG_FILE_NAME']}", 0644);
$GLOBALS['URL_SSL'] = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? true : false;
$GLOBALS['URL_PATH'] = ($GLOBALS['URL_SSL']) ? "https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}" : "http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
$GLOBALS['DB_PORT'] = parse_url($_POST['dbhost'], PHP_URL_PORT);



//Restart log if user starts from step 1
if ($_POST['action_step'] == 1) {
    $GLOBALS['LOG_FILE_HANDLE'] = @fopen($GLOBALS['LOG_FILE_NAME'], "w+");
} else {
    $GLOBALS['LOG_FILE_HANDLE'] = @fopen($GLOBALS['LOG_FILE_NAME'], "a+");
}
?>
	
<?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

define('ERR_CONFIG_FOUND',		'A wp-config.php already exists in this location.  This error prevents users from accidentally overwriting the wrong directories contents.  You have two options: <ul><li>Empty this root directory except for the package and installer and try again.</li><li>Delete just the wp-config.php file and try again.  This will over-write all other files in the directory.</li></ul>');
define('ERR_ZIPNOTFOUND',		'The packaged zip file was not found. Be sure the zip package is in the same directory as the installer file.  If you are trying to reinstall a package you can copy the package from the "' . DUPLICATOR_SSDIR_NAME . '" directory back up to your root which is the same location as your installer.php file.');
define('ERR_ZIPEXTRACTION',		'Failed in extracting zip file. Please be sure the archive is completely downloaded. Try to extract the archive manually to make sure the file is not corrupted.');
define('ERR_ZIPMANUAL',			'When choosing manual package extraction, the contents of the package must already be extracted and the wp-config.php and database.sql files must be present in the same directory as the installer.php for the process to continue.  Please manually extract the package into the current directory before continuing in manual extraction mode.  Also validate that the wp-config.php and database.sql files are present.');
define('ERR_MAKELOG',			'PHP is having issues writing to the log file <b>' . DupUtil::set_safe_path($GLOBALS['CURRENT_ROOT_PATH']) . '\installer-log.txt .</b> In order for the Duplicator to proceed validate your owner/group and permission settings for PHP on this path. Try temporarily setting you permissions to 777 to see if the issue gets resolved.  If you are on a shared hosting environment please contact your hosting company and tell them you are getting errors writing files to the path above when using PHP.');
define('ERR_ZIPARCHIVE',		'In order to extract the archive.zip file the PHP ZipArchive module must be installed.  Please read the FAQ for more details.  You can still install this package but you will need to check the Manual package extraction checkbox found in the Advanced Options.  Please read the online user guide for details in performing a manual package extraction.');
define('ERR_MYSQLI_SUPPORT',	'In order to complete an install the mysqli extension for PHP is required. If you are on a hosted server please contact your host and request that mysqli be enabled.  For more information visit: http://php.net/manual/en/mysqli.installation.php');
define('ERR_DBCONNECT',			'DATABASE CONNECTION FAILED!<br/>');
define('ERR_DBCONNECT_CREATE',  'DATABASE CREATION FAILURE!<br/> Unable to create database "%s". Check to make sure the user has "Create" privileges.  Some hosts will restrict creation of a database only through the cpanel.  Try creating the database manually to proceed with installation.  If the database already exists then check the radio button labeled "Connect and Remove All Data" which will remove all existing tables.');
define('ERR_DBTRYCLEAN',		'DATABASE CREATION FAILURE!<br/> Unable to remove all tables from database "%s".<br/>  Please remove all tables from this database and try the installation again.');
define('ERR_DBCREATE',			'The database "%s" does not exists.<br/>  Change mode to create in order to create a new database.');
define('ERR_DBEMPTY',			'The database "%s" has "%s" tables.  The Duplicator only works with an EMPTY database.  Enable the action "Connect and Remove All Data" radio button to remove all tables and or create a new database. Some hosting providers do not allow table removal from scripts.  In this case you will need to login to your hosting providers control panel and remove the tables manually.  Please contact your hosting provider for further details.  Always backup all your data before proceeding!');

/** * *****************************************************
 * DUPX_Log  
 * Class used to log information  */

class DUPX_Log {

    /** METHOD: LOG
     *  Used to write debug info to the text log file
     *  @param string $msg		Any text data
     *  @param int $loglevel	Log level
     */
    static public function Info($msg, $logging = 1) {
        if ($logging <= $GLOBALS["LOGGING"]) {
            @fwrite($GLOBALS["LOG_FILE_HANDLE"], "{$msg}\n");
        }
    }
	
	static public function Error($errorMessage) {
        die("<div class='dup-ui-error'><b style='color:#B80000;'>INSTALL ERROR!</b><br/>{$errorMessage}</div><br/>");
    }
}
?>


<?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

/** * *****************************************************
 *  CLASS::DupUtil
 *  Various Static Utility methods for working with the installer */
class DupUtil {



    /** METHOD: GET_MICROTIME
     * Get current microtime as a float. Can be used for simple profiling.
     */
    static public function get_microtime() {
        return microtime(true);
    }

    /** METHOD: ELAPSED_TIME
     * Return a string with the elapsed time.
     * Order of $end and $start can be switched. 
     */
    static public function elapsed_time($end, $start) {
        return sprintf("%.4f sec.", abs($end - $start));
    }

    /** METHOD: ESC_HTML_ATTR
     * @param string $string Thing that needs escaping
     * @param bool $echo   Do we echo or return?
     * @return string    Escaped string. 
     */
    static public function esc_html_attr($string = '', $echo = false) {
        $output = htmlentities($string, ENT_QUOTES, 'UTF-8');
        if ($echo)
            echo $output;
        else
            return $output;
    }

    /** METHOD: TABLE_COUNT
     *  Count the tables in a given database
     *  @param string $_POST['dbname']		Database to count tables in 
     */
    static public function dbtable_count($conn, $dbname) {
        $res = mysqli_query($conn, "SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = '{$dbname}' ");
        $row = mysqli_fetch_row($res);
        return is_null($row) ? 0 : $row[0];
    }

    /** METHOD: TABLE_ROW_COUNT
     *  Returns the table count
     *  @param string $conn	        A valid link resource
     *  @param string $table_name	A valid table name
     */
    static public function table_row_count($conn, $table_name) {
        $total = mysqli_query($conn, "SELECT COUNT(*) FROM `$table_name`");
        if ($total) {
            $total = @mysqli_fetch_array($total);
            return $total[0];
        } else {
            return 0;
        }
    }

    /** METHOD: ADD_ENDING_SLASH
     *  Adds a slash to the end of a path
     *  @param string $path		A path
     */
    static public function add_slash($path) {
        $last_char = substr($path, strlen($path) - 1, 1);
        if ($last_char != '/') {
            $path .= '/';
        }
        return $path;
    }

    /** METHOD: SET_SAFE_PATH
     *  Makes path safe for any OS
     *  Paths should ALWAYS READ be "/"
     * 		uni: /home/path/file.xt
     * 		win:  D:/home/path/file.txt 
     *  @param string $path		The path to make safe
     */
    static public function set_safe_path($path) {
        return str_replace("\\", "/", $path);
    }

    static public function unset_safe_path($path) {
        return str_replace("/", "\\", $path);
    }

    /** METHOD: FCGI_FLUSH
     *  PHP_SAPI for fcgi requires a data flush of at least 256
     *  bytes every 40 seconds or else it forces a script hault
     */
    static public function fcgi_flush() {
        echo(str_repeat(' ', 256));
        @flush();
    }

    /** METHOD: COPY_FILE
     *  A safe method used to copy larger files
     *  @param string $source		The path to the file being copied
     *  @param string $destination	The path to the file being made
     */
    static public function copy_file($source, $destination) {
        $sp = fopen($source, 'r');
        $op = fopen($destination, 'w');

        while (!feof($sp)) {
            $buffer = fread($sp, 512);  // use a buffer of 512 bytes
            fwrite($op, $buffer);
        }
        // close handles
        fclose($op);
        fclose($sp);
    }

    /** METHOD: string_has_value
     *  Finds a string in a file and returns true if found
     *  @param array  $list		A list of strings to search for
     *  @param string $haystack	The string to search in
     */
    static public function string_has_value($list, $file) {
        foreach ($list as $var) {
            if (strstr($file, $var) !== false) {
                return true;
            }
        }
        return false;
    }

    /** METHOD: get_active_plugins
     *  Returns the active plugins for a package
     *  @param  conn   $dbh	 A database connection handle
     *  @return array  $list A list of active plugins
     */
    static public function get_active_plugins($dbh) {
        $query = @mysqli_query($dbh, "SELECT option_value FROM `{$GLOBALS['FW_TABLEPREFIX']}options` WHERE option_name = 'active_plugins' ");
        if ($query) {
            $row = @mysqli_fetch_array($query);
            $all_plugins = unserialize($row[0]);
            if (is_array($all_plugins)) {
                return $all_plugins;
            }
        }
        return array();
    }

    /** METHOD: get_database_tables
     *  Returns the tables for a database
     *  @param  conn   $dbh	 A database connection handle	 
     *  @return array  $list A list of all table names
     */
    static public function get_database_tables($dbh) {
        $query = @mysqli_query($dbh, 'SHOW TABLES');
        if ($query) {
            while ($table = @mysqli_fetch_array($query)) {
                $all_tables[] = $table[0];
            }
            if (is_array($all_tables)) {
                return $all_tables;
            }
        }
        return array();
    }
	
    /**
     * MySQL connection support for sock
     * @param same as mysqli_connect
     * @return database connection handle
     */	
	static public function mysqli_connect( $host, $username, $password, $dbname = '', $port = null ) {
		if ( ! $port ) {
			$port = ini_get("mysqli.default_port");
			$port = empty($port) ? 3306 : $port;
		}

		if ( 'sock' === substr( $host, -4 ) ) {
			$url_parts = parse_url( $host );
			$dbh = @mysqli_connect( 'localhost', $username, $password, $dbname, null, $url_parts['path'] );
		}
		else {
			$dbh = @mysqli_connect( $host, $username, $password, $dbname, $port );
		}
		return $dbh;
	}
	

    /**
     * MySQL database version number
     * @param conn $dbh Database connection handle
     * @return false|string false on failure, version number on success
     */
    static public function mysql_version($dbh) {
        if (function_exists('mysqli_get_server_info')) {
            return preg_replace('/[^0-9.].*/', '', mysqli_get_server_info($dbh));
        } else {
            return 0;
        }
    }

    /**
     * MySQL server variable
     * @param conn $dbh Database connection handle
     * @return string the server variable to query for
     */
    static public function mysql_variable_value($dbh, $variable) {
        $result = @mysqli_query($dbh, "SHOW VARIABLES LIKE '{$variable}'");
        $row = @mysqli_fetch_array($result);
        @mysqli_free_result($result);
        return isset($row[1]) ? $row[1] : null;
    }

    /**
     * Determine if a MySQL database supports a particular feature
     * @param conn $dbh Database connection handle	 
     * @param string $feature the feature to check for
     * @return bool
     */
    static public function mysql_has_ability($dbh, $feature) {
        $version = self::mysql_version($dbh);

        switch (strtolower($feature)) {
            case 'collation' :
            case 'group_concat' :
            case 'subqueries' :
                return version_compare($version, '4.1', '>=');
            case 'set_charset' :
                return version_compare($version, '5.0.7', '>=');
        };
        return false;
    }

    /**
     * Sets the MySQL connection's character set.
     * @param resource $dbh     The resource given by mysqli_connect
     * @param string   $charset The character set (optional)
     * @param string   $collate The collation (optional)
     */
    static public function mysql_set_charset($dbh, $charset = null, $collate = null) {

        $charset = (!isset($charset) ) ? $GLOBALS['DBCHARSET_DEFAULT'] : $charset;
        $collate = (!isset($collate) ) ? $GLOBALS['DBCOLLATE_DEFAULT'] : $collate;

        if (self::mysql_has_ability($dbh, 'collation') && !empty($charset)) {
            if (function_exists('mysqli_set_charset') && self::mysql_has_ability($dbh, 'set_charset')) {
                return mysqli_set_charset($dbh, $charset);
            } else {
                $sql = " SET NAMES {$charset}";
                if (!empty($collate))
                    $sql .= " COLLATE {$collate}";
                return mysqli_query($dbh, $sql);
            }
        }
    }

    /**
     *  READABLE_BYTESIZE
     *  Display human readable byte sizes
     *  @param string $size		The size in bytes
     */
    static public function readable_bytesize($size) {
        try {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');
            for ($i = 0; $size >= 1024 && $i < 4; $i++)
                $size /= 1024;
            return round($size, 2) . $units[$i];
        } catch (Exception $e) {
            return "n/a";
        }
    }
	
	/**
     *  PREG_REPLACEMENT_QUOTE
     *  The characters that are special in the replacement value of preg_replace are not the 
	 *  same characters that are special in the pattern
     *  @param string $str		The string to replace on
     */
	static public function preg_replacement_quote($str) {
		return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $str);
	}
	
	
	/**
     *  IS_WEB_CONNECTED
     *  Check to see if the internet is accessable
	 *  @param string $url		A url e.g without prefix "ajax.googleapis.com"
	 *  @param string $port		A valid port number
	 *  @return bool
     */
	static public function is_url_active($url, $port) {
		if (function_exists('fsockopen')) {
			$port = isset($port) && is_integer($port) ? $port : 80;
			$connected = @fsockopen($url, $port); //website and port
			if ($connected){
				$is_conn = true;
				@fclose($connected);
			} else {
				$is_conn = false; 
			}
			return $is_conn;
		} else {
			return false;
		}
	}
	
	
}
?>

<?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

/** * *****************************************************
 * DUPX_Config 
 * Class used to update and edit web server configuration files  */

class DUPX_Config {
	

    /** 
     *  Clear .htaccess and web.config files and backup
     */
    static public function Reset() {
		
		DUPX_Log::Info("\nWEB SERVER CONFIGURATION FILE RESET:");

		//Apache
		@copy('.htaccess', '.htaccess.orig');
		@unlink('.htaccess');
		//IIS
		@copy('web.config', 'web.config.orig');
		@unlink('web.config');

		DUPX_Log::Info("- Backup of .htaccess/web.config made to .orig");
		DUPX_Log::Info("- Reset of .htaccess/web.config files");
		$tmp_htaccess = '# RESET FOR DUPLICATOR INSTALLER USEAGE';
		file_put_contents('.htaccess', $tmp_htaccess);
		@chmod('.htaccess', 0644);
    }
	
	
	    /** METHOD: ResetHTACCESS
     *  Resetst the .htaccess file
     */
    static public function Setup() {
		
		if (! isset($_POST['url_new'])) {
			return;
		}
		
		DUPX_Log::Info("\nWEB SERVER CONFIGURATION FILE BASIC SETUP:");
		$currdata = parse_url($_POST['url_old']);
		$newdata  = parse_url($_POST['url_new']);
		$currpath = DupUtil::add_slash(isset($currdata['path']) ? $currdata['path'] : "");
		$newpath  = DupUtil::add_slash(isset($newdata['path'])  ? $newdata['path'] : "");

		$tmp_htaccess = <<<HTACCESS
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$newpath}
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$newpath}index.php [L]
</IfModule>
# END WordPress
HTACCESS;

		file_put_contents('.htaccess', $tmp_htaccess);
		@chmod('.htaccess', 0644);
		DUPX_Log::Info("created basic .htaccess file.  If using IIS web.config this process will need to be done manually.");

    }
	
	
}
?>


<?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

/** * *****************************************************
 * CLASS::DUPDBTEXTSWAP
 * Walks every table in db that then walks every row and column replacing searches with replaces
 * large tables are split into 50k row blocks to save on memory. */
class DUPX_Serializer {

	/**
	 * LOG ERRORS
	 */
	static public function log_errors($report) {
		if (!empty($report['errsql'])) {
			DUPX_Log::Info("====================================");
			DUPX_Log::Info("DATA-REPLACE ERRORS (MySQL)");
			foreach ($report['errsql'] as $error) {
				DUPX_Log::Info($error);
			}
			DUPX_Log::Info("");
		}
		if (!empty($report['errser'])) {
			DUPX_Log::Info("====================================");
			DUPX_Log::Info("DATA-REPLACE ERRORS (Serialization):");
			foreach ($report['errser'] as $error) {
				DUPX_Log::Info($error);
			}
			DUPX_Log::Info("");
		}
		if (!empty($report['errkey'])) {
			DUPX_Log::Info("====================================");
			DUPX_Log::Info("DATA-REPLACE ERRORS (Key):");
			DUPX_Log::Info('Use SQL: SELECT @row := @row + 1 as row, t.* FROM some_table t, (SELECT @row := 0) r');
			foreach ($report['errkey'] as $error) {
				DUPX_Log::Info($error);
			}
		}
	}

	/**
	 * LOG STATS
	 */
	static public function log_stats($report) {
		if (!empty($report) && is_array($report)) {
			$stats  = "--------------------------------------\n";
			$stats .= sprintf("SEARCH1:\t'%s' \nREPLACE1:\t'%s' \n", $_POST['url_old'], $_POST['url_new']);
			$stats .= sprintf("SEARCH2:\t'%s' \nREPLACE2:\t'%s' \n", $_POST['path_old'], $_POST['path_new']);
			$stats .= sprintf("SCANNED:\tTables:%d | Rows:%d | Cells:%d \n", $report['scan_tables'], $report['scan_rows'], $report['scan_cells']);
			$stats .= sprintf("UPDATED:\tTables:%d | Rows:%d |Cells:%d \n", $report['updt_tables'], $report['updt_rows'], $report['updt_cells']);
			$stats .= sprintf("ERRORS:\t\t%d \nRUNTIME:\t%f sec", $report['err_all'], $report['time']);
			DUPX_Log::Info($stats);
		}
	}
	
	/**
	 * Returns only the text type columns of a table ignoring all numeric types
	 */
	static public function getTextColumns($conn, $table) {
	
		$type_where  = "type NOT LIKE 'tinyint%' AND ";
		$type_where .= "type NOT LIKE 'smallint%' AND ";
		$type_where .= "type NOT LIKE 'mediumint%' AND ";
		$type_where .= "type NOT LIKE 'int%' AND ";
		$type_where .= "type NOT LIKE 'bigint%' AND ";
		$type_where .= "type NOT LIKE 'float%' AND ";
		$type_where .= "type NOT LIKE 'double%' AND ";
		$type_where .= "type NOT LIKE 'decimal%' AND ";
		$type_where .= "type NOT LIKE 'numeric%' AND ";
		$type_where .= "type NOT LIKE 'date%' AND ";
		$type_where .= "type NOT LIKE 'time%' AND ";
		$type_where .= "type NOT LIKE 'year%' ";

		$result = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` WHERE {$type_where}");
		if (!$result) { 
			return null;
		} 
		$fields = array(); 
		if (mysqli_num_rows($result) > 0) { 
			while ($row = mysqli_fetch_assoc($result)) { 
				$fields[] = $row['Field']; 
			} 
		} 
		
		//Return Primary which is needed for index lookup
		$result = mysqli_query($conn, "SHOW INDEX FROM `{$table}` WHERE KEY_NAME LIKE '%PRIMARY%'");
		if (mysqli_num_rows($result) > 0) { 
			while ($row = mysqli_fetch_assoc($result)) { 
				$fields[] = $row['Column_name']; 
			} 
		} 
	
		return (count($fields) > 0) ? $fields : null;
	}

	/**
	 * LOAD
	 * Begins the processing for replace logic
	 * @param mysql  $conn 		 The db connection object
	 * @param array  $list       Key value pair of 'search' and 'replace' arrays
	 * @param array  $tables     The tables we want to look at.
	 * @return array Collection of information gathered during the run.
	 */
	static public function load($conn, $list = array(), $tables = array(), $cols = array(), $fullsearch = false) {
		$exclude_cols = $cols;

		$report = array('scan_tables' => 0, 'scan_rows' => 0, 'scan_cells' => 0,
			'updt_tables' => 0, 'updt_rows' => 0, 'updt_cells' => 0,
			'errsql' => array(), 'errser' => array(), 'errkey' => array(),
			'errsql_sum' => 0, 'errser_sum' => 0, 'errkey_sum' => 0,
			'time' => '', 'err_all' => 0);
		
		$walk_function = create_function('&$str', '$str = "`$str`";');

		$profile_start = DupUtil::get_microtime();
		if (is_array($tables) && !empty($tables)) {
			
			foreach ($tables as $table) {
				$report['scan_tables']++;
				$columns = array();

				// Get a list of columns in this table
				$fields = mysqli_query($conn, 'DESCRIBE ' . $table);
				while ($column = mysqli_fetch_array($fields)) {
					$columns[$column['Field']] = $column['Key'] == 'PRI' ? true : false;
				}

				// Count the number of rows we have in the table if large we'll split into blocks
				$row_count = mysqli_query($conn, "SELECT COUNT(*) FROM `{$table}`");
				$rows_result = mysqli_fetch_array($row_count);
				@mysqli_free_result($row_count);
				$row_count = $rows_result[0];
				if ($row_count == 0) {
					DUPX_Log::Info("{$table}^ ({$row_count})");
					continue;
				}

				$page_size = 25000;
				$offset = ($page_size + 1);
				$pages = ceil($row_count / $page_size);
				
				// Grab the columns of the table.  Only grab text based columns because 
				// they are the only data types that should allow any type of search/replace logic
				$colList = '*';
				$colMsg  = '*';
				if (! $fullsearch) {
					$colList = self::getTextColumns($conn, $table);
					if ($colList != null && is_array($colList)) {
						array_walk($colList, $walk_function);
						$colList = implode(',', $colList);
					} 
					$colMsg = (empty($colList)) ? '*' : '~';
				}
				
				if (empty($colList)) {
					DUPX_Log::Info("{$table}^ ({$row_count})");
					continue;
				} else {
					DUPX_Log::Info("{$table}{$colMsg} ({$row_count})");
				}

				//Paged Records
				for ($page = 0; $page < $pages; $page++) {

					$current_row = 0;
					$start = $page * $page_size;
					$end   = $start + $page_size;
					$sql = sprintf("SELECT {$colList} FROM `%s` LIMIT %d, %d", $table, $start, $offset);
					$data  = mysqli_query($conn, $sql);

					if (!$data)
						$report['errsql'][] = mysqli_error($conn);
					
					$scan_count = ($row_count < $end) ? $row_count : $end;
					DUPX_Log::Info("\tScan => {$start} of {$scan_count}", 2);
					//DEBUG ONLY:
					//DUPX_Log::Info("\t{$sql}", 3);

					//Loops every row
					while ($row = mysqli_fetch_array($data)) {
						$report['scan_rows']++;
						$current_row++;
						$upd_col = array();
						$upd_sql = array();
						$where_sql = array();
						$upd = false;
						$serial_err = 0;

						//Loops every cell
						foreach ($columns as $column => $primary_key) {
							if (in_array($column, $exclude_cols)) {
								continue;
							}

							$report['scan_cells']++;
							$edited_data = $data_to_fix = $row[$column];
							$base64coverted = false;

							//Only replacing string values
							if (!empty($row[$column]) && !is_numeric($row[$column])) {

								//Base 64 detection
								if (base64_decode($row[$column], true)) {
									$decoded = base64_decode($row[$column], true);
									if (self::is_serialized($decoded)) {
										$edited_data = $decoded;
										$base64coverted = true;
									}
								}

								//Replace logic - level 1: simple check on basic serilized strings
								foreach ($list as $item) {
									$edited_data = self::recursive_unserialize_replace($item['search'], $item['replace'], $edited_data);
								}

								//Replace logic - level 2: repair larger/complex serilized strings
								$serial_check = self::fix_serial_string($edited_data);
								if ($serial_check['fixed']) {
									$edited_data = $serial_check['data'];
								} elseif ($serial_check['tried'] && !$serial_check['fixed']) {
									$serial_err++;
								}
							}

							//Change was made
							if ($edited_data != $data_to_fix || $serial_err > 0) {
								$report['updt_cells']++;
								//Base 64 encode
								if ($base64coverted) {
									$edited_data = base64_encode($edited_data);
								}
								$upd_col[] = $column;
								$upd_sql[] = $column . ' = "' . mysqli_real_escape_string($conn, $edited_data) . '"';
								$upd = true;
							}

							if ($primary_key) {
								$where_sql[] = $column . ' = "' . mysqli_real_escape_string($conn, $data_to_fix) . '"';
							}
						}

						//PERFORM ROW UPDATE
						if ($upd && !empty($where_sql)) {
							$sql = "UPDATE `{$table}` SET " . implode(', ', $upd_sql) . ' WHERE ' . implode(' AND ', array_filter($where_sql));
							$result = mysqli_query($conn, $sql) or $report['errsql'][] = mysqli_error($conn);
							//DEBUG ONLY:
							DUPX_Log::Info("\t{$sql}", 3);
							if ($result) {
								if ($serial_err > 0) {
									$report['errser'][] = "SELECT " . implode(', ', $upd_col) . " FROM `{$table}`  WHERE " . implode(' AND ', array_filter($where_sql)) . ';';
								}
								$report['updt_rows']++;
							}
						} elseif ($upd) {
							$report['errkey'][] = sprintf("Row [%s] on Table [%s] requires a manual update.", $current_row, $table);
						}
					}
					DupUtil::fcgi_flush();
					@mysqli_free_result($data);
				}

				if ($upd) {
					$report['updt_tables']++;
				}
			}
		}
		$profile_end = DupUtil::get_microtime();
		$report['time'] = DupUtil::elapsed_time($profile_end, $profile_start);
		$report['errsql_sum'] = empty($report['errsql']) ? 0 : count($report['errsql']);
		$report['errser_sum'] = empty($report['errser']) ? 0 : count($report['errser']);
		$report['errkey_sum'] = empty($report['errkey']) ? 0 : count($report['errkey']);
		$report['err_all'] = $report['errsql_sum'] + $report['errser_sum'] + $report['errkey_sum'];
		return $report;
	}

	/**
	 * Take a serialised array and unserialise it replacing elements and
	 * unserialising any subordinate arrays and performing the replace.
	 * @param string $from       String we're looking to replace.
	 * @param string $to         What we want it to be replaced with
	 * @param array  $data       Used to pass any subordinate arrays back to in.
	 * @param bool   $serialised Does the array passed via $data need serialising.
	 * @return array	The original array with all elements replaced as needed. 
	 */
	static private function recursive_unserialize_replace($from = '', $to = '', $data = '', $serialised = false) {

		// some unseriliased data cannot be re-serialised eg. SimpleXMLElements
		try {

			if (is_string($data) && ($unserialized = @unserialize($data)) !== false) {
				$data = self::recursive_unserialize_replace($from, $to, $unserialized, true);
			} elseif (is_array($data)) {
				$_tmp = array();
				foreach ($data as $key => $value) {
					$_tmp[$key] = self::recursive_unserialize_replace($from, $to, $value, false);
				}
				$data = $_tmp;
				unset($_tmp);
			} elseif (is_object($data)) {
				$dataClass = get_class($data);
				$_tmp = new $dataClass();
				foreach ($data as $key => $value) {
					$_tmp->$key = self::recursive_unserialize_replace($from, $to, $value, false);
				}
				$data = $_tmp;
				unset($_tmp);
			} else {
				if (is_string($data)) {
					$data = str_replace($from, $to, $data);
				}
			}

			if ($serialised)
				return serialize($data);
		} catch (Exception $error) {
			DUPX_Log::Info("\nRECURSIVE UNSERIALIZE ERROR: With string\n" . $error, 2);
		}
		return $data;
	}

	/**
	 *  IS_SERIALIZED
	 *  Test if a string in properly serialized */
	static public function is_serialized($data) {
		$test = @unserialize(($data));
		return ($test !== false || $test === 'b:0;') ? true : false;
	}

	/**
	 *  FIX_STRING
	 *  Fixes the string length of a string object that has been serialized but the length is broken
	 *  @param string $data	The string ojbect to recalculate the size on.
	 *  @return 
	 */
	static private function fix_serial_string($data) {

		$result = array('data' => $data, 'fixed' => false, 'tried' => false);

		if (preg_match("/s:[0-9]+:/", $data)) {
			if (!self::is_serialized($data)) {
				$regex = '!(?<=^|;)s:(\d+)(?=:"(.*?)";(?:}|a:|s:|b:|d:|i:|o:|N;))!s';
				$serial_string = preg_match('/^s:[0-9]+:"(.*$)/s', trim($data), $matches);
				//Nested serial string
				if ($serial_string) {
					$inner = preg_replace_callback($regex, 'DupDBTextSwap::fix_string_callback', rtrim($matches[1], '";'));
					$serialized_fixed = 's:' . strlen($inner) . ':"' . $inner . '";';
				} else {
					$serialized_fixed = preg_replace_callback($regex, 'DupDBTextSwap::fix_string_callback', $data);
				}

				if (self::is_serialized($serialized_fixed)) {
					$result['data'] = $serialized_fixed;
					$result['fixed'] = true;
				}
				$result['tried'] = true;
			}
		}
		return $result;
	}

	static private function fix_string_callback($matches) {
		return 's:' . strlen(($matches[2]));
	}

}
?>

<?php
if (isset($_POST['action_ajax'])) {
    switch ($_POST['action_ajax']) {
        case "1" :
            ?> <?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

//POST PARAMS
$_POST['dbaction']		= isset($_POST['dbaction']) ? $_POST['dbaction'] : 'create';
$_POST['dbnbsp']		= (isset($_POST['dbnbsp']) && $_POST['dbnbsp'] == '1') ? true : false;
$_POST['ssl_admin']		= (isset($_POST['ssl_admin'])) ? true : false;
$_POST['ssl_login']		= (isset($_POST['ssl_login'])) ? true : false;
$_POST['cache_wp']		= (isset($_POST['cache_wp'])) ? true : false;
$_POST['cache_path']	= (isset($_POST['cache_path'])) ? true : false;
$_POST['package_name']	= isset($_POST['package_name']) ? $_POST['package_name'] : null;
$_POST['zip_manual']	= (isset($_POST['zip_manual']) && $_POST['zip_manual'] == '1') ? true : false;

//LOGGING
$POST_LOG = $_POST;
unset($POST_LOG['dbpass']);
ksort($POST_LOG);

//PAGE VARS
$root_path		= DupUtil::set_safe_path($GLOBALS['CURRENT_ROOT_PATH']);
$package_path	= "{$root_path}/{$_POST['package_name']}";
$package_size	= @filesize($package_path);
$ajax1_start	= DupUtil::get_microtime();
$zip_support	= class_exists('ZipArchive') ? 'Enabled' : 'Not Enabled';
$JSON = array();
$JSON['pass'] = 0;

/* JSON RESPONSE: Most sites have warnings turned off by default, but if they're turned on the warnings
cause errors in the JSON data Here we hide the status so warning level is reset at it at the end*/
$ajax1_error_level = error_reporting();
error_reporting(E_ERROR);

//===============================
//DATABASE TEST CONNECTION
//===============================
if (isset($_GET['dbtest'])) {
	
	$html    = "";
	$dbConn  = DupUtil::mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass'], null, $_POST['dbport']);
	$dbErr	 = mysqli_connect_error();
	$dbFound = mysqli_select_db($dbConn, $_POST['dbname']);

	$tstSrv   = ($dbConn)  ? "<div class='dup-pass'>Success</div>" : "<div class='dup-fail'>Fail</div>";
	$tstDB    = ($dbFound) ? "<div class='dup-pass'>Success</div>" : "<div class='dup-fail'>Fail</div>";
	$html	 .= "<div class='dup-db-test'>";
	$html	 .= "<small>Connection String:<br/>Server={$_POST['dbhost']}; Database={$_POST['dbname']}; Uid={$_POST['dbuser']}; Pwd={$_POST['dbpass']}; Port={$_POST['dbport']}</small>";
	$html	 .= "<label>Server Connected:</label> {$tstSrv} <br/>";
	$html	 .= "<label>Database Found:</label>   {$tstDB} <br/><br/>";

	if ($_POST['dbaction'] == 'create'){
		$tblcount = DupUtil::dbtable_count($dbConn, $_POST['dbname']);
		$html .= ($tblcount > 0) 
		? "<div class='dup-fail'><b>WARNING</b></div><br/>" . sprintf(ERR_DBEMPTY, $_POST['dbname'], $tblcount)
		: "";
	}
	$html .= "</div>";
	die($html);
}

//===============================
//ERROR MESSAGES
//===============================
//ERR_MAKELOG
($GLOBALS['LOG_FILE_HANDLE'] != false) or DUPX_Log::Error(ERR_MAKELOG);

//ERR_MYSQLI_SUPPORT
function_exists('mysqli_connect') or DUPX_Log::Error(ERR_MYSQLI_SUPPORT);

//ERR_DBCONNECT
$dbh = DupUtil::mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass']);
($dbh) or DUPX_Log::Error(ERR_DBCONNECT . mysqli_connect_error());
if ($_POST['dbaction'] == 'empty') {
	mysqli_select_db($dbh, $_POST['dbname']) or DUPX_Log::Error(sprintf(ERR_DBCREATE, $_POST['dbname']));
}
//ERR_DBEMPTY
if ($_POST['dbaction'] == 'create' ) {
	$tblcount = DupUtil::dbtable_count($dbh, $_POST['dbname']);
	if ($tblcount > 0) {
		DUPX_Log::Error(sprintf(ERR_DBEMPTY, $_POST['dbname'], $tblcount));
	}
}

//ERR_ZIPMANUAL
if ($_POST['zip_manual']) {
	if (!file_exists("wp-config.php") && !file_exists("database.sql")) {
		DUPX_Log::Error(ERR_ZIPMANUAL);
	}
} else {
	//ERR_CONFIG_FOUND
	(!file_exists('wp-config.php')) 
		or DUPX_Log::Error(ERR_CONFIG_FOUND);
	//ERR_ZIPNOTFOUND
	(is_readable("{$package_path}")) 
		or DUPX_Log::Error(ERR_ZIPNOTFOUND);
}

DUPX_Log::Info("********************************************************************************");
DUPX_Log::Info('DUPLICATOR INSTALL-LOG');
DUPX_Log::Info('STEP1 START @ ' . @date('h:i:s'));
DUPX_Log::Info('NOTICE: Do NOT post to public sites or forums');
DUPX_Log::Info("********************************************************************************");
DUPX_Log::Info("VERSION:\t{$GLOBALS['FW_DUPLICATOR_VERSION']}");
DUPX_Log::Info("PHP:\t\t" . phpversion() . ' | SAPI: ' . php_sapi_name());
DUPX_Log::Info("SERVER:\t\t{$_SERVER['SERVER_SOFTWARE']}");
DUPX_Log::Info("DOC ROOT:\t{$root_path}");
DUPX_Log::Info("DOC ROOT 755:\t" . var_export($GLOBALS['CHOWN_ROOT_PATH'], true));
DUPX_Log::Info("LOG FILE 644:\t" . var_export($GLOBALS['CHOWN_LOG_PATH'], true));
DUPX_Log::Info("BUILD NAME:\t{$GLOBALS['FW_SECURE_NAME']}");
DUPX_Log::Info("REQUEST URL:\t{$GLOBALS['URL_PATH']}");

$log  = "--------------------------------------\n";
$log .= "POST DATA\n";
$log .= "--------------------------------------\n";
$log .= print_r($POST_LOG, true);
DUPX_Log::Info($log, 2);


//====================================================================================================
//UNZIP & FILE SETUP - Extract the zip file and prep files
//====================================================================================================
$log  = "\n********************************************************************************\n";
$log .= "ARCHIVE SETUP\n";
$log .= "********************************************************************************\n";
$log .= "NAME:\t{$_POST['package_name']}\n";
$log .= "SIZE:\t" . DupUtil::readable_bytesize(@filesize($_POST['package_name'])) . "\n";
$log .= "ZIP:\t{$zip_support} (ZipArchive Support)";
DUPX_Log::Info($log);

$zip_start = DupUtil::get_microtime();

if ($_POST['zip_manual']) {
	DUPX_Log::Info("\n** PACKAGE EXTRACTION IS IN MANUAL MODE ** \n");
} else {
	if ($GLOBALS['FW_PACKAGE_NAME'] != $_POST['package_name']) {
		$log  = "\n--------------------------------------\n";
		$log .= "WARNING: This package set may be incompatible!  \nBelow is a summary of the package this installer was built with and the package used. \n";
		$log .= "To guarantee accuracy the installer and archive should match. For details see the online FAQs.";
		$log .= "\nCREATED WITH:\t{$GLOBALS['FW_PACKAGE_NAME']} \nPROCESSED WITH:\t{$_POST['package_name']}  \n";
		$log .= "--------------------------------------\n";
		DUPX_Log::Info($log);
	}
	
	if (! class_exists('ZipArchive')) {
		DUPX_Log::Info("ERROR: Stopping install process.  Trying to extract without ZipArchive module installed.  Please use the 'Manual Package extraction' mode to extract zip file.");
		DUPX_Log::Error(ERR_ZIPARCHIVE);
	}

	$target = $root_path;
	$zip = new ZipArchive();
	if ($zip->open($_POST['package_name']) === TRUE) {
		DUPX_Log::Info("EXTRACTING");
		@$zip->extractTo($target);
		$close_response = $zip->close();
		$log  = print_r($zip, true);
		$log .= "COMPLETE: " . var_export($close_response, true);
		DUPX_Log::Info($log);
	} else {
		DUPX_Log::Error(ERR_ZIPEXTRACTION);
	}
	$zip = null;
}

//===============================
//WP-CONFIG: wp-config
//===============================
$wpconfig = @file_get_contents('wp-config.php', true);

$patterns = array(
	"/'DB_NAME',\s*'.*?'/",
	"/'DB_USER',\s*'.*?'/",
	"/'DB_PASSWORD',\s*'.*?'/",
	"/'DB_HOST',\s*'.*?'/");

$replace = array(
	"'DB_NAME', "	  . '\'' . $_POST['dbname']				. '\'',
	"'DB_USER', "	  . '\'' . $_POST['dbuser']				. '\'',
	"'DB_PASSWORD', " . '\'' . DupUtil::preg_replacement_quote($_POST['dbpass']) . '\'',
	"'DB_HOST', "	  . '\'' . $_POST['dbhost']				. '\'');

//SSL CHECKS
if ($_POST['ssl_admin']) {
	if (! strstr($wpconfig, 'FORCE_SSL_ADMIN')) {
		$wpconfig = $wpconfig . PHP_EOL . "define('FORCE_SSL_ADMIN', true);";
	}
} else {
	array_push($patterns, "/'FORCE_SSL_ADMIN',\s*true/");
	array_push($replace,  "'FORCE_SSL_ADMIN', false");
}

if ($_POST['ssl_login']) {
	if (! strstr($wpconfig, 'FORCE_SSL_LOGIN')) {
		$wpconfig = $wpconfig . PHP_EOL . "define('FORCE_SSL_LOGIN', true);";
	}
} else {
	array_push($patterns, "/'FORCE_SSL_LOGIN',\s*true/");
	array_push($replace, "'FORCE_SSL_LOGIN', false");
}

//CACHE CHECKS
if ($_POST['cache_wp']) {
	if (! strstr($wpconfig, 'WP_CACHE')) {
		$wpconfig = $wpconfig . PHP_EOL . "define('WP_CACHE', true);";
	}
} else {
	array_push($patterns, "/'WP_CACHE',\s*true/");
	array_push($replace,  "'WP_CACHE', false");
}
if (! $_POST['cache_path']) {
	array_push($patterns, "/'WPCACHEHOME',\s*'.*?'/");
	array_push($replace,  "'WPCACHEHOME', ''");
}

$wpconfig = preg_replace($patterns, $replace, $wpconfig);
file_put_contents('wp-config.php', $wpconfig);
$wpconfig = null;

//CONFIG FILE RESETS
DUPX_Config::Reset();


//===============================
//DATABASE SCRIPT
//===============================
@chmod("{$root_path}/database.sql", 0777);
$sql_file = @file_get_contents('database.sql', true);
if ($sql_file == false || strlen($sql_file) < 10) {
	$sql_file = file_get_contents('installer-data.sql', true);
	if ($sql_file == false || strlen($sql_file) < 10) {
		DUPX_Log::Info("ERROR: Unable to read from the extracted database.sql file .\nValidate the permissions and/or group-owner rights on directory '{$root_path}'\n");
	}
}

//Complex Subject See: http://webcollab.sourceforge.net/unicode.html
//Removes invalid space characters
if ($_POST['dbnbsp']) {
	DUPX_Log::Info("ran fix non-breaking space characters\n");
	$sql_file = preg_replace('/\xC2\xA0/', ' ', $sql_file);
}

//Write new contents to install-data.sql
@chmod($sql_result_file_path, 0777);
file_put_contents($GLOBALS['SQL_FILE_NAME'], $sql_file);

$sql_result_file_data = explode(";\n", $sql_file);
$sql_result_file_length = count($sql_result_file_data);
$sql_result_file_path = "{$root_path}/{$GLOBALS['SQL_FILE_NAME']}";
$sql_file = null;

if (!is_readable($sql_result_file_path) || filesize($sql_result_file_path) == 0) {
	DUPX_Log::Info("ERROR: Unable to create new sql file {$GLOBALS['SQL_FILE_NAME']}.\nValidate the permissions and/or group-owner rights on directory '{$root_path}' and file '{$GLOBALS['SQL_FILE_NAME']}'\n");
}

DUPX_Log::Info("\nUPDATED FILES:");
DUPX_Log::Info("- SQL FILE:  '{$sql_result_file_path}'");
DUPX_Log::Info("- WP-CONFIG: '{$root_path}/wp-config.php'");
$zip_end = DupUtil::get_microtime();
DUPX_Log::Info("\nARCHIVE RUNTIME: " . DupUtil::elapsed_time($zip_end, $zip_start));
DUPX_Log::Info("\n");
DupUtil::fcgi_flush();


//====================================================================================================
//DATABASE ROUTINES
//====================================================================================================

@mysqli_query($dbh, "SET wait_timeout = {$GLOBALS['DB_MAX_TIME']}");
@mysqli_query($dbh, "SET max_allowed_packet = {$GLOBALS['DB_MAX_PACKETS']}");
DupUtil::mysql_set_charset($dbh, $_POST['dbcharset'], $_POST['dbcollate']);

//Set defaults incase the variable could not be read
$dbvar_maxtime = DupUtil::mysql_variable_value($dbh, 'wait_timeout');
$dbvar_maxpacks = DupUtil::mysql_variable_value($dbh, 'max_allowed_packet');
$dbvar_maxtime = is_null($dbvar_maxtime) ? 300 : $dbvar_maxtime;
$dbvar_maxpacks = is_null($dbvar_maxpacks) ? 1048576 : $dbvar_maxpacks;


DUPX_Log::Info("{$GLOBALS['SEPERATOR1']}");
DUPX_Log::Info('DATABASE-ROUTINES');
DUPX_Log::Info("{$GLOBALS['SEPERATOR1']}");
DUPX_Log::Info("--------------------------------------");
DUPX_Log::Info("SERVER ENVIROMENT");
DUPX_Log::Info("--------------------------------------");
DUPX_Log::Info("MYSQL VERSION:\t" . mysqli_get_server_info($dbh));
DUPX_Log::Info("TIMEOUT:\t{$dbvar_maxtime}");
DUPX_Log::Info("MAXPACK:\t{$dbvar_maxpacks}");

//CREATE DB
switch ($_POST['dbaction']) {
	case "create":	
		mysqli_query($dbh, "CREATE DATABASE IF NOT EXISTS `{$_POST['dbname']}`");
		mysqli_select_db($dbh, $_POST['dbname'])
		or DUPX_Log::Error(sprintf(ERR_DBCONNECT_CREATE, $_POST['dbname']));
		break;
	case "empty":	
		//DROP DB TABLES
		$drop_log = "Database already empty. Ready for install.";
		$sql = "SHOW TABLES FROM `{$_POST['dbname']}`";
		$found_tables = null;
		if ($result = mysqli_query($dbh, $sql)) {
			while ($row = mysqli_fetch_row($result)) {
				$found_tables[] = $row[0];
			}
			if (count($found_tables) > 0) {
				foreach ($found_tables as $table_name) {
					$sql = "DROP TABLE `{$_POST['dbname']}`.`{$table_name}`";
					if (!$result = mysqli_query($dbh, $sql)) {
						DUPX_Log::Error(sprintf(ERR_DBTRYCLEAN, $_POST['dbname']));
					}
				}
			}
			$drop_log = 'removed (' . count($found_tables) . ') tables';
		}
		break;
}


//WRITE DATA
DUPX_Log::Info("--------------------------------------");
DUPX_Log::Info("DATABASE RESULTS");
DUPX_Log::Info("--------------------------------------");
$profile_start = DupUtil::get_microtime();
$fcgi_buffer_pool = 5000;
$fcgi_buffer_count = 0;
$dbquery_rows = 0;
$dbtable_rows = 1;
$dbquery_errs = 0;
$counter = 0;
@mysqli_autocommit($dbh, false);
while ($counter < $sql_result_file_length) {

	$query_strlen = strlen(trim($sql_result_file_data[$counter]));
	if ($dbvar_maxpacks < $query_strlen) {
		DUPX_Log::Info("**ERROR** Query size limit [length={$query_strlen}] [sql=" . substr($sql_result_file_data[$counter], 75) . "...]");
		$dbquery_errs++;
	} elseif ($query_strlen > 0) {
		@mysqli_free_result(@mysqli_query($dbh, ($sql_result_file_data[$counter])));
		$err = mysqli_error($dbh);
		//Check to make sure the connection is alive
		if (!empty($err)) {

			if (!mysqli_ping($dbh)) {
				mysqli_close($dbh);
				$dbh = DupUtil::mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass'], $_POST['dbname']);
			}
			DUPX_Log::Info("**ERROR** database error write '{$err}' - [sql=" . substr($sql_result_file_data[$counter], 0, 75) . "...]");
			$dbquery_errs++;

		//Buffer data to browser to keep connection open				
		} else {
			if ($fcgi_buffer_count++ > $fcgi_buffer_pool) {
				$fcgi_buffer_count = 0;
				DupUtil::fcgi_flush();
			}
			$dbquery_rows++;
		}
	}
	$counter++;
}
@mysqli_commit($dbh);
@mysqli_autocommit($dbh, true);

DUPX_Log::Info("ERRORS FOUND:\t{$dbquery_errs}");
DUPX_Log::Info("DROP TABLE:\t{$drop_log}");
DUPX_Log::Info("QUERIES RAN:\t{$dbquery_rows}\n");

$dbtable_count = 0;
if ($result = mysqli_query($dbh, "SHOW TABLES")) {
	while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
		$table_rows = DupUtil::table_row_count($dbh, $row[0]);
		$dbtable_rows += $table_rows;
		DUPX_Log::Info("{$row[0]}: ({$table_rows})");
		$dbtable_count++;
	}
	@mysqli_free_result($result);
}

if ($dbtable_count == 0) {
	DUPX_Log::Info("NOTICE: You may have to manually run the installer-data.sql to validate data input. Also check to make sure your installer file is correct and the
		table prefix '{$GLOBALS['FW_TABLEPREFIX']}' is correct for this particular version of WordPress. \n");
}


//DATA CLEANUP: Perform Transient Cache Cleanup
//Remove all duplicator entries and record this one since this is a new install.
$dbdelete_count = 0;
@mysqli_query($dbh, "DELETE FROM `{$GLOBALS['FW_TABLEPREFIX']}duplicator_packages`");
$dbdelete_count1 = @mysqli_affected_rows($dbh) or 0;
@mysqli_query($dbh, "DELETE FROM `{$GLOBALS['FW_TABLEPREFIX']}options` WHERE `option_name` LIKE ('_transient%') OR `option_name` LIKE ('_site_transient%')");
$dbdelete_count2 = @mysqli_affected_rows($dbh) or 0;
$dbdelete_count = (abs($dbdelete_count1) + abs($dbdelete_count2));
DUPX_Log::Info("Removed '{$dbdelete_count}' cache/transient rows");
//Reset Duplicator Options
foreach ($GLOBALS['FW_OPTS_DELETE'] as $value) {
	mysqli_query($dbh, "DELETE FROM `{$GLOBALS['FW_TABLEPREFIX']}options` WHERE `option_name` = '{$value}'");	
}

@mysqli_close($dbh);

$profile_end = DupUtil::get_microtime();
DUPX_Log::Info("\nSECTION RUNTIME: " . DupUtil::elapsed_time($profile_end, $profile_start));

//FINAL RESULTS
$ajax1_end = DupUtil::get_microtime();
$ajax1_sum = DupUtil::elapsed_time($ajax1_end, $ajax1_start);
DUPX_Log::Info("\n{$GLOBALS['SEPERATOR1']}");
DUPX_Log::Info('STEP1 COMPLETE @ ' . @date('h:i:s') . " - TOTAL RUNTIME: {$ajax1_sum}");
DUPX_Log::Info("{$GLOBALS['SEPERATOR1']}");

$JSON['pass'] = 1;
$JSON['table_count'] = $dbtable_count;
$JSON['table_rows']  = $dbtable_rows;
$JSON['query_errs']  = $dbquery_errs;
echo json_encode($JSON);
error_reporting($ajax1_error_level);
die('');
?> <?php break;
        case "2" :
            ?> <?php
// Exit if accessed directly
if (! defined('DUPLICATOR_INIT')) {
	$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
	$_baseURL =  "http://" . $_baseURL;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $_baseURL");
	exit; 
}

/* JSON RESPONSE: Most sites have warnings turned off by default, but if they're turned on the warnings
cause errors in the JSON data Here we hide the status so warning level is reset at it at the end*/
$ajax2_error_level = error_reporting();
error_reporting(E_ERROR);

//====================================================================================================
//DATABASE UPDATES
//====================================================================================================

$ajax2_start = DupUtil::get_microtime();

//MYSQL CONNECTION
$dbh = DupUtil::mysqli_connect($_POST['dbhost'], $_POST['dbuser'], html_entity_decode($_POST['dbpass']), $_POST['dbname'], $_POST['dbport']);
$charset_server = @mysqli_character_set_name($dbh);
@mysqli_query($dbh, "SET wait_timeout = {$GLOBALS['DB_MAX_TIME']}");
DupUtil::mysql_set_charset($dbh, $_POST['dbcharset'], $_POST['dbcollate']);

//POST PARAMS
$_POST['blogname'] = mysqli_real_escape_string($dbh, $_POST['blogname']);
$_POST['postguid'] = isset($_POST['postguid']) && $_POST['postguid'] == 1 ? 1 : 0;
$_POST['fullsearch'] = isset($_POST['fullsearch']) && $_POST['fullsearch'] == 1 ? 1 : 0;
$_POST['path_old'] = isset($_POST['path_old']) ? trim($_POST['path_old']) : null;
$_POST['path_new'] = isset($_POST['path_new']) ? trim($_POST['path_new']) : null;
$_POST['siteurl'] = isset($_POST['siteurl']) ? rtrim(trim($_POST['siteurl']), '/') : null;
$_POST['tables'] = isset($_POST['tables']) && is_array($_POST['tables']) ? array_map('stripcslashes', $_POST['tables']) : array();
$_POST['url_old'] = isset($_POST['url_old']) ? trim($_POST['url_old']) : null;
$_POST['url_new'] = isset($_POST['url_new']) ? rtrim(trim($_POST['url_new']), '/') : null;

//LOGGING
$POST_LOG = $_POST;
unset($POST_LOG['tables']);
unset($POST_LOG['plugins']);
unset($POST_LOG['dbpass']);
ksort($POST_LOG);

$date = @date('h:i:s');
$charset_client = @mysqli_character_set_name($dbh);

$log = <<<LOG
\n\n
********************************************************************************
DUPLICATOR INSTALL-LOG
STEP2 START @ {$date}
NOTICE: Do not post to public sites or forums
********************************************************************************
CHARSET SERVER:\t{$charset_server}
CHARSET CLIENT:\t {$charset_client} \n
LOG;
DUPX_Log::Info($log);

//Detailed logging
$log  = "--------------------------------------\n";
$log .= "POST DATA\n";
$log .= "--------------------------------------\n";
$log .= print_r($POST_LOG, true);		
$log .= "--------------------------------------\n";
$log .= "SCANNED TABLES\n";
$log .= "--------------------------------------\n";
$log .= (isset($_POST['tables']) && count($_POST['tables'] > 0)) 
		? print_r($_POST['tables'], true) 
		: 'No tables selected to update';
$log .= "--------------------------------------\n";
$log .= "KEEP PLUGINS ACTIVE\n";
$log .= "--------------------------------------\n";
$log .= (isset($_POST['plugins']) && count($_POST['plugins'] > 0)) 
		? print_r($_POST['plugins'], true) 
		: 'No plugins selected for activation';
DUPX_Log::Info($log, 2);

//UPDATE SETTINGS
$serial_plugin_list = (isset($_POST['plugins']) && count($_POST['plugins'] > 0)) ? @serialize($_POST['plugins']) : '';
mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}options` SET option_value = '{$_POST['blogname']}' WHERE option_name = 'blogname' ");
mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}options` SET option_value = '{$serial_plugin_list}'  WHERE option_name = 'active_plugins' ");

$log  = "--------------------------------------\n";
$log .= "SERIALIZER ENGINE\n";
$log .= "[*] scan every column\n";
$log .= "[~] scan only text columns\n";
$log .= "[^] no searchable columns\n";
$log .= "--------------------------------------";
DUPX_Log::Info($log);

$url_old_json = str_replace('"', "", json_encode($_POST['url_old']));
$url_new_json = str_replace('"', "", json_encode($_POST['url_new']));
$path_old_json = str_replace('"', "", json_encode($_POST['path_old']));
$path_new_json = str_replace('"', "", json_encode($_POST['path_new']));

array_push($GLOBALS['REPLACE_LIST'], 
		array('search' => $_POST['url_old'],  'replace' => $_POST['url_new']), 
		array('search' => $url_old_json,	  'replace' => $url_new_json), 
		array('search' => $_POST['path_old'], 'replace' => $_POST['path_new']), 
		array('search' => $path_old_json,	  'replace' => $path_new_json), 		
		array('search' => rtrim(DupUtil::unset_safe_path($_POST['path_old']), '\\'), 'replace' => rtrim($_POST['path_new'], '/'))
);

@mysqli_autocommit($dbh, false);
$report = DUPX_Serializer::load($dbh, $GLOBALS['REPLACE_LIST'], $_POST['tables'], $GLOBALS['TABLES_SKIP_COLS'], $_POST['fullsearch']);
@mysqli_commit($dbh);
@mysqli_autocommit($dbh, true);


//BUILD JSON RESPONSE
$JSON = array();
$JSON['step1'] = json_decode(urldecode($_POST['json']));
$JSON['step2'] = $report;
$JSON['step2']['warn_all'] = 0;
$JSON['step2']['warnlist'] = array();

DUPX_Serializer::log_stats($report);
DUPX_Serializer::log_errors($report);

//Reset the postguid data
if ($_POST['postguid']) {
	mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}posts` SET guid = REPLACE(guid, '{$_POST['url_new']}', '{$_POST['url_old']}')");
	$update_guid = @mysqli_affected_rows($dbh) or 0;
	DUPX_Log::Info("Reverted '{$update_guid}' post guid columns back to '{$_POST['url_old']}'");
}

/* FINAL UPDATES: Must happen after the global replace to prevent double pathing
  http://xyz.com/abc01 will become http://xyz.com/abc0101  with trailing data */
mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}options` SET option_value = '{$_POST['url_new']}'  WHERE option_name = 'home' ");
mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}options` SET option_value = '{$_POST['siteurl']}'  WHERE option_name = 'siteurl' ");


//====================================================================================================
//FINAL CLEANUP
//====================================================================================================
DUPX_Log::Info("\n********************************************************************************");
DUPX_Log::Info('START FINAL CLEANUP: ' . @date('h:i:s'));
DUPX_Log::Info("********************************************************************************");

/*CREATE NEW USER LOGIC */
if (strlen($_POST['wp_username']) >= 4 && strlen($_POST['wp_password']) >= 6) {
	
	$newuser_check = mysqli_query($dbh, "SELECT COUNT(*) AS count FROM `{$GLOBALS['FW_TABLEPREFIX']}users` WHERE user_login = '{$_POST['wp_username']}' ");
	$newuser_row   = mysqli_fetch_row($newuser_check);
    $newuser_count = is_null($newuser_row) ? 0 : $newuser_row[0];
	
	if ($newuser_count == 0) {
	
		$newuser_datetime =	@date("Y-m-d H:i:s");
		$newuser_security = mysqli_real_escape_string($dbh, 'a:1:{s:13:"administrator";s:1:"1";}');

		$newuser_test1 = @mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}users` 
			(`user_login`, `user_pass`, `user_nicename`, `user_email`, `user_registered`, `user_activation_key`, `user_status`, `display_name`) 
			VALUES ('{$_POST['wp_username']}', MD5('{$_POST['wp_password']}'), '{$_POST['wp_username']}', '', '{$newuser_datetime}', '', '0', '{$_POST['wp_username']}')");

		$newuser_insert_id = mysqli_insert_id($dbh);

		$newuser_test2 = @mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` 
				(`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', '{$GLOBALS['FW_TABLEPREFIX']}capabilities', '{$newuser_security}')");

		$newuser_test3 = @mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` 
				(`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', '{$GLOBALS['FW_TABLEPREFIX']}user_level', '10')");
				
		//Misc Meta-Data Settings:
		@mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', 'rich_editing', 'true')");
		@mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', 'admin_color',  'fresh')");
		@mysqli_query($dbh, "INSERT INTO `{$GLOBALS['FW_TABLEPREFIX']}usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES ('{$newuser_insert_id}', 'nickname', '{$_POST['wp_username']}')");

		if ($newuser_test1 && $newuser_test2 && $newuser_test3) {
			DUPX_Log::Info("NEW WP-ADMIN USER: New username '{$_POST['wp_username']}' was created successfully \n ");
		} else {
			$newuser_warnmsg = "NEW WP-ADMIN USER: Failed to create the user '{$_POST['wp_username']}' \n ";
			$JSON['step2']['warnlist'][] = $newuser_warnmsg;
			DUPX_Log::Info($newuser_warnmsg);
		}			
	} 
	else {
		$newuser_warnmsg = "NEW WP-ADMIN USER: Username '{$_POST['wp_username']}' already exists in the database.  Unable to create new account \n";
		$JSON['step2']['warnlist'][] = $newuser_warnmsg;
		DUPX_Log::Info($newuser_warnmsg);
	}
}

/*MU Updates*/
$mu_newDomain = parse_url($_POST['url_new']);
$mu_oldDomain = parse_url($_POST['url_old']);
$mu_newDomainHost = $mu_newDomain['host'];
$mu_oldDomainHost = $mu_oldDomain['host'];
$mu_updates = @mysqli_query($dbh, "UPDATE `{$GLOBALS['FW_TABLEPREFIX']}blogs` SET domain = '{$mu_newDomainHost}' WHERE domain = '{$mu_oldDomainHost}'");
if ($mu_updates) {
	DUPX_Log::Info("Update MU table blogs: domain {$mu_newDomainHost} ");
} else {
	DUPX_Log::Info("UPDATE `{$GLOBALS['FW_TABLEPREFIX']}blogs` SET domain = '{$mu_newDomainHost}' WHERE domain = '{$mu_oldDomainHost}'");
}


/*UPDATE WP-CONFIG FILE */
$patterns = array("/'WP_HOME',\s*'.*?'/", 
				  "/'WP_SITEURL',\s*'.*?'/",
				  "/'DOMAIN_CURRENT_SITE',\s*'.*?'/");

$replace = array("'WP_HOME', "    . '\''		  . $_POST['url_new'] . '\'',
				 "'WP_SITEURL', " . '\''		  . $_POST['url_new'] . '\'',
				 "'DOMAIN_CURRENT_SITE', " . '\'' . $mu_newDomainHost     . '\'');

$config_file = @file_get_contents('wp-config.php', true);
$config_file = preg_replace($patterns, $replace, $config_file);
file_put_contents('wp-config.php', $config_file);


//Create Snapshots directory
if (!file_exists(DUPLICATOR_SSDIR_NAME)) {
	mkdir(DUPLICATOR_SSDIR_NAME, 0755);
}
$fp = fopen(DUPLICATOR_SSDIR_NAME . '/index.php', 'w');
fclose($fp);


//===============================
//WARNING TESTS
//===============================
DUPX_Log::Info("\n--------------------------------------");
DUPX_Log::Info("WARNINGS");
DUPX_Log::Info("--------------------------------------");
$config_vars = array('WP_CONTENT_DIR', 'WP_CONTENT_URL', 'WPCACHEHOME', 'COOKIE_DOMAIN', 'WP_SITEURL', 'WP_HOME', 'WP_TEMP_DIR');
$config_found = DupUtil::string_has_value($config_vars, $config_file);

//Files
if ($config_found) {
	$msg = 'WP-CONFIG WARNING: The wp-config.php has one or more of these values "' . implode(", ", $config_vars) . '" which may cause issues please validate these values by opening the file.';
	$JSON['step2']['warnlist'][] = $msg;
	DUPX_Log::Info($msg);
}

//Database
$result = @mysqli_query($dbh, "SELECT option_value FROM `{$GLOBALS['FW_TABLEPREFIX']}options` WHERE option_name IN ('upload_url_path','upload_path')");
if ($result) {
	while ($row = mysqli_fetch_row($result)) {
		if (strlen($row[0])) {
			$msg = "MEDIA SETTINGS WARNING: The table '{$GLOBALS['FW_TABLEPREFIX']}options' has at least one the following values ['upload_url_path','upload_path'] set please validate settings. These settings can be changed in the wp-admin by going to Settings->Media area see 'Uploading Files'";
			$JSON['step2']['warnlist'][] = $msg;
			DUPX_Log::Info($msg);
			break;
		}
	}
}

if (empty($JSON['step2']['warnlist'])) {
	DUPX_Log::Info("No Warnings Found\n");
}

$JSON['step2']['warn_all'] = empty($JSON['step2']['warnlist']) ? 0 : count($JSON['step2']['warnlist']);

mysqli_close($dbh);
@unlink('database.sql');

//CONFIG Setup
DUPX_Config::Setup();

$ajax2_end = DupUtil::get_microtime();
$ajax2_sum = DupUtil::elapsed_time($ajax2_end, $ajax2_start);
DUPX_Log::Info("********************************************************************************");
DUPX_Log::Info('STEP 2 COMPLETE @ ' . @date('h:i:s') . " - TOTAL RUNTIME: {$ajax2_sum}");
DUPX_Log::Info("********************************************************************************");

$JSON['step2']['pass'] = 1;
error_reporting($ajax2_error_level);
die(json_encode($JSON));
?> <?php
            break;
    }
    @fclose($GLOBALS["LOG_FILE_HANDLE"]);
    die("");
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex,nofollow">
	<title>Wordpress Duplicator</title>
	<?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
?>
<?php if( DupUtil::is_url_active("ajax.googleapis.com", 443) ): ?>
	<link rel='stylesheet' href='//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.css' type='text/css' media='all' />
<?php else: ?>
	<style type="text/css">
	/*! jQuery UI - v1.10.3 - 2013-07-24
	* http://jqueryui.com
	* Includes: jquery.ui.core.css, jquery.ui.resizable.css, jquery.ui.selectable.css, jquery.ui.accordion.css, jquery.ui.autocomplete.css, jquery.ui.button.css, jquery.ui.datepicker.css, jquery.ui.dialog.css, jquery.ui.menu.css, jquery.ui.progressbar.css, jquery.ui.slider.css, jquery.ui.spinner.css, jquery.ui.tabs.css, jquery.ui.tooltip.css
	* To view and modify this theme, visit http://jqueryui.com/themeroller/?ffDefault=Verdana%2CArial%2Csans-serif&fwDefault=normal&fsDefault=1.1em&cornerRadius=4px&bgColorHeader=cccccc&bgTextureHeader=highlight_soft&bgImgOpacityHeader=75&borderColorHeader=aaaaaa&fcHeader=222222&iconColorHeader=222222&bgColorContent=ffffff&bgTextureContent=flat&bgImgOpacityContent=75&borderColorContent=aaaaaa&fcContent=222222&iconColorContent=222222&bgColorDefault=e6e6e6&bgTextureDefault=glass&bgImgOpacityDefault=75&borderColorDefault=d3d3d3&fcDefault=555555&iconColorDefault=888888&bgColorHover=dadada&bgTextureHover=glass&bgImgOpacityHover=75&borderColorHover=999999&fcHover=212121&iconColorHover=454545&bgColorActive=ffffff&bgTextureActive=glass&bgImgOpacityActive=65&borderColorActive=aaaaaa&fcActive=212121&iconColorActive=454545&bgColorHighlight=fbf9ee&bgTextureHighlight=glass&bgImgOpacityHighlight=55&borderColorHighlight=fcefa1&fcHighlight=363636&iconColorHighlight=2e83ff&bgColorError=fef1ec&bgTextureError=glass&bgImgOpacityError=95&borderColorError=cd0a0a&fcError=cd0a0a&iconColorError=cd0a0a&bgColorOverlay=aaaaaa&bgTextureOverlay=flat&bgImgOpacityOverlay=0&opacityOverlay=30&bgColorShadow=aaaaaa&bgTextureShadow=flat&bgImgOpacityShadow=0&opacityShadow=30&thicknessShadow=8px&offsetTopShadow=-8px&offsetLeftShadow=-8px&cornerRadiusShadow=8px
	* Copyright 2013 jQuery Foundation and other contributors Licensed MIT */
	.ui-helper-hidden{display:none}.ui-helper-hidden-accessible{border:0;clip:rect(0 0 0 0);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px}.ui-helper-reset{margin:0;padding:0;border:0;outline:0;line-height:1.3;text-decoration:none;font-size:100%;list-style:none}.ui-helper-clearfix:before,.ui-helper-clearfix:after{content:"";display:table;border-collapse:collapse}.ui-helper-clearfix:after{clear:both}.ui-helper-clearfix{min-height:0}.ui-helper-zfix{width:100%;height:100%;top:0;left:0;position:absolute;opacity:0;filter:Alpha(Opacity=0)}.ui-front{z-index:100}.ui-state-disabled{cursor:default!important}.ui-icon{display:block;text-indent:-99999px;overflow:hidden;background-repeat:no-repeat}.ui-widget-overlay{position:fixed;top:0;left:0;width:100%;height:100%}.ui-resizable{position:relative}.ui-resizable-handle{position:absolute;font-size:.1px;display:block}.ui-resizable-disabled .ui-resizable-handle,.ui-resizable-autohide .ui-resizable-handle{display:none}.ui-resizable-n{cursor:n-resize;height:7px;width:100%;top:-5px;left:0}.ui-resizable-s{cursor:s-resize;height:7px;width:100%;bottom:-5px;left:0}.ui-resizable-e{cursor:e-resize;width:7px;right:-5px;top:0;height:100%}.ui-resizable-w{cursor:w-resize;width:7px;left:-5px;top:0;height:100%}.ui-resizable-se{cursor:se-resize;width:12px;height:12px;right:1px;bottom:1px}.ui-resizable-sw{cursor:sw-resize;width:9px;height:9px;left:-5px;bottom:-5px}.ui-resizable-nw{cursor:nw-resize;width:9px;height:9px;left:-5px;top:-5px}.ui-resizable-ne{cursor:ne-resize;width:9px;height:9px;right:-5px;top:-5px}.ui-selectable-helper{position:absolute;z-index:100;border:1px dotted #000}.ui-accordion .ui-accordion-header{display:block;cursor:pointer;position:relative;margin-top:2px;padding:.5em .5em .5em .7em;min-height:0}.ui-accordion .ui-accordion-icons{padding-left:2.2em}.ui-accordion .ui-accordion-noicons{padding-left:.7em}.ui-accordion .ui-accordion-icons .ui-accordion-icons{padding-left:2.2em}.ui-accordion .ui-accordion-header .ui-accordion-header-icon{position:absolute;left:.5em;top:50%;margin-top:-8px}.ui-accordion .ui-accordion-content{padding:1em 2.2em;border-top:0;overflow:auto}.ui-autocomplete{position:absolute;top:0;left:0;cursor:default}.ui-button{display:inline-block;position:relative;padding:0;line-height:normal;margin-right:.1em;cursor:pointer;vertical-align:middle;text-align:center;overflow:visible}.ui-button,.ui-button:link,.ui-button:visited,.ui-button:hover,.ui-button:active{text-decoration:none}.ui-button-icon-only{width:2.2em}button.ui-button-icon-only{width:2.4em}.ui-button-icons-only{width:3.4em}button.ui-button-icons-only{width:3.7em}.ui-button .ui-button-text{display:block;line-height:normal}.ui-button-text-only .ui-button-text{padding:.4em 1em}.ui-button-icon-only .ui-button-text,.ui-button-icons-only .ui-button-text{padding:.4em;text-indent:-9999999px}.ui-button-text-icon-primary .ui-button-text,.ui-button-text-icons .ui-button-text{padding:.4em 1em .4em 2.1em}.ui-button-text-icon-secondary .ui-button-text,.ui-button-text-icons .ui-button-text{padding:.4em 2.1em .4em 1em}.ui-button-text-icons .ui-button-text{padding-left:2.1em;padding-right:2.1em}input.ui-button{padding:.4em 1em}.ui-button-icon-only .ui-icon,.ui-button-text-icon-primary .ui-icon,.ui-button-text-icon-secondary .ui-icon,.ui-button-text-icons .ui-icon,.ui-button-icons-only .ui-icon{position:absolute;top:50%;margin-top:-8px}.ui-button-icon-only .ui-icon{left:50%;margin-left:-8px}.ui-button-text-icon-primary .ui-button-icon-primary,.ui-button-text-icons .ui-button-icon-primary,.ui-button-icons-only .ui-button-icon-primary{left:.5em}.ui-button-text-icon-secondary .ui-button-icon-secondary,.ui-button-text-icons .ui-button-icon-secondary,.ui-button-icons-only .ui-button-icon-secondary{right:.5em}.ui-buttonset{margin-right:7px}.ui-buttonset .ui-button{margin-left:0;margin-right:-.3em}input.ui-button::-moz-focus-inner,button.ui-button::-moz-focus-inner{border:0;padding:0}.ui-datepicker{width:17em;padding:.2em .2em 0;display:none}.ui-datepicker .ui-datepicker-header{position:relative;padding:.2em 0}.ui-datepicker .ui-datepicker-prev,.ui-datepicker .ui-datepicker-next{position:absolute;top:2px;width:1.8em;height:1.8em}.ui-datepicker .ui-datepicker-prev-hover,.ui-datepicker .ui-datepicker-next-hover{top:1px}.ui-datepicker .ui-datepicker-prev{left:2px}.ui-datepicker .ui-datepicker-next{right:2px}.ui-datepicker .ui-datepicker-prev-hover{left:1px}.ui-datepicker .ui-datepicker-next-hover{right:1px}.ui-datepicker .ui-datepicker-prev span,.ui-datepicker .ui-datepicker-next span{display:block;position:absolute;left:50%;margin-left:-8px;top:50%;margin-top:-8px}.ui-datepicker .ui-datepicker-title{margin:0 2.3em;line-height:1.8em;text-align:center}.ui-datepicker .ui-datepicker-title select{font-size:1em;margin:1px 0}.ui-datepicker select.ui-datepicker-month-year{width:100%}.ui-datepicker select.ui-datepicker-month,.ui-datepicker select.ui-datepicker-year{width:49%}.ui-datepicker table{width:100%;font-size:.9em;border-collapse:collapse;margin:0 0 .4em}.ui-datepicker th{padding:.7em .3em;text-align:center;font-weight:700;border:0}.ui-datepicker td{border:0;padding:1px}.ui-datepicker td span,.ui-datepicker td a{display:block;padding:.2em;text-align:right;text-decoration:none}.ui-datepicker .ui-datepicker-buttonpane{background-image:none;margin:.7em 0 0;padding:0 .2em;border-left:0;border-right:0;border-bottom:0}.ui-datepicker .ui-datepicker-buttonpane button{float:right;margin:.5em .2em .4em;cursor:pointer;padding:.2em .6em .3em;width:auto;overflow:visible}.ui-datepicker .ui-datepicker-buttonpane button.ui-datepicker-current{float:left}.ui-datepicker.ui-datepicker-multi{width:auto}.ui-datepicker-multi .ui-datepicker-group{float:left}.ui-datepicker-multi .ui-datepicker-group table{width:95%;margin:0 auto .4em}.ui-datepicker-multi-2 .ui-datepicker-group{width:50%}.ui-datepicker-multi-3 .ui-datepicker-group{width:33.3%}.ui-datepicker-multi-4 .ui-datepicker-group{width:25%}.ui-datepicker-multi .ui-datepicker-group-last .ui-datepicker-header,.ui-datepicker-multi .ui-datepicker-group-middle .ui-datepicker-header{border-left-width:0}.ui-datepicker-multi .ui-datepicker-buttonpane{clear:left}.ui-datepicker-row-break{clear:both;width:100%;font-size:0}.ui-datepicker-rtl{direction:rtl}.ui-datepicker-rtl .ui-datepicker-prev{right:2px;left:auto}.ui-datepicker-rtl .ui-datepicker-next{left:2px;right:auto}.ui-datepicker-rtl .ui-datepicker-prev:hover{right:1px;left:auto}.ui-datepicker-rtl .ui-datepicker-next:hover{left:1px;right:auto}.ui-datepicker-rtl .ui-datepicker-buttonpane{clear:right}.ui-datepicker-rtl .ui-datepicker-buttonpane button{float:left}.ui-datepicker-rtl .ui-datepicker-buttonpane button.ui-datepicker-current,.ui-datepicker-rtl .ui-datepicker-group{float:right}.ui-datepicker-rtl .ui-datepicker-group-last .ui-datepicker-header,.ui-datepicker-rtl .ui-datepicker-group-middle .ui-datepicker-header{border-right-width:0;border-left-width:1px}.ui-dialog{position:absolute;top:0;left:0;padding:.2em;outline:0}.ui-dialog .ui-dialog-titlebar{padding:.4em 1em;position:relative}.ui-dialog .ui-dialog-title{float:left;margin:.1em 0;white-space:nowrap;width:90%;overflow:hidden;text-overflow:ellipsis}.ui-dialog .ui-dialog-titlebar-close{position:absolute;right:.3em;top:50%;width:21px;margin:-10px 0 0 0;padding:1px;height:20px}.ui-dialog .ui-dialog-content{position:relative;border:0;padding:.5em 1em;background:0;overflow:auto}.ui-dialog .ui-dialog-buttonpane{text-align:left;border-width:1px 0 0;background-image:none;margin-top:.5em;padding:.3em 1em .5em .4em}.ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset{float:right}.ui-dialog .ui-dialog-buttonpane button{margin:.5em .4em .5em 0;cursor:pointer}.ui-dialog .ui-resizable-se{width:12px;height:12px;right:-5px;bottom:-5px;background-position:16px 16px}.ui-draggable .ui-dialog-titlebar{cursor:move}.ui-menu{list-style:none;padding:2px;margin:0;display:block;outline:0}.ui-menu .ui-menu{margin-top:-3px;position:absolute}.ui-menu .ui-menu-item{margin:0;padding:0;width:100%;list-style-image:url(data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7)}.ui-menu .ui-menu-divider{margin:5px -2px 5px -2px;height:0;font-size:0;line-height:0;border-width:1px 0 0}.ui-menu .ui-menu-item a{text-decoration:none;display:block;padding:2px .4em;line-height:1.5;min-height:0;font-weight:400}.ui-menu .ui-menu-item a.ui-state-focus,.ui-menu .ui-menu-item a.ui-state-active{font-weight:400;margin:-1px}.ui-menu .ui-state-disabled{font-weight:400;margin:.4em 0 .2em;line-height:1.5}.ui-menu .ui-state-disabled a{cursor:default}.ui-menu-icons{position:relative}.ui-menu-icons .ui-menu-item a{position:relative;padding-left:2em}.ui-menu .ui-icon{position:absolute;top:.2em;left:.2em}.ui-menu .ui-menu-icon{position:static;float:right}.ui-progressbar{height:2em;text-align:left;overflow:hidden}.ui-progressbar .ui-progressbar-value{margin:-1px;height:100%}.ui-progressbar .ui-progressbar-overlay{background:url(images/animated-overlay.gif);height:100%;filter:alpha(opacity=25);opacity:.25}.ui-progressbar-indeterminate .ui-progressbar-value{background-image:none}.ui-slider{position:relative;text-align:left}.ui-slider .ui-slider-handle{position:absolute;z-index:2;width:1.2em;height:1.2em;cursor:default}.ui-slider .ui-slider-range{position:absolute;z-index:1;font-size:.7em;display:block;border:0;background-position:0 0}.ui-slider.ui-state-disabled .ui-slider-handle,.ui-slider.ui-state-disabled .ui-slider-range{filter:inherit}.ui-slider-horizontal{height:.8em}.ui-slider-horizontal .ui-slider-handle{top:-.3em;margin-left:-.6em}.ui-slider-horizontal .ui-slider-range{top:0;height:100%}.ui-slider-horizontal .ui-slider-range-min{left:0}.ui-slider-horizontal .ui-slider-range-max{right:0}.ui-slider-vertical{width:.8em;height:100px}.ui-slider-vertical .ui-slider-handle{left:-.3em;margin-left:0;margin-bottom:-.6em}.ui-slider-vertical .ui-slider-range{left:0;width:100%}.ui-slider-vertical .ui-slider-range-min{bottom:0}.ui-slider-vertical .ui-slider-range-max{top:0}.ui-spinner{position:relative;display:inline-block;overflow:hidden;padding:0;vertical-align:middle}.ui-spinner-input{border:0;background:0;color:inherit;padding:0;margin:.2em 0;vertical-align:middle;margin-left:.4em;margin-right:22px}.ui-spinner-button{width:16px;height:50%;font-size:.5em;padding:0;margin:0;text-align:center;position:absolute;cursor:default;display:block;overflow:hidden;right:0}.ui-spinner a.ui-spinner-button{border-top:0;border-bottom:0;border-right:0}.ui-spinner .ui-icon{position:absolute;margin-top:-8px;top:50%;left:0}.ui-spinner-up{top:0}.ui-spinner-down{bottom:0}.ui-spinner .ui-icon-triangle-1-s{background-position:-65px -16px}.ui-tabs{position:relative;padding:.2em}.ui-tabs .ui-tabs-nav{margin:0;padding:.2em .2em 0}.ui-tabs .ui-tabs-nav li{list-style:none;float:left;position:relative;top:0;margin:1px .2em 0 0;border-bottom-width:0;padding:0;white-space:nowrap}.ui-tabs .ui-tabs-nav li a{float:left;padding:.5em 1em;text-decoration:none}.ui-tabs .ui-tabs-nav li.ui-tabs-active{margin-bottom:-1px;padding-bottom:1px}.ui-tabs .ui-tabs-nav li.ui-tabs-active a,.ui-tabs .ui-tabs-nav li.ui-state-disabled a,.ui-tabs .ui-tabs-nav li.ui-tabs-loading a{cursor:text}.ui-tabs .ui-tabs-nav li a,.ui-tabs-collapsible .ui-tabs-nav li.ui-tabs-active a{cursor:pointer}.ui-tabs .ui-tabs-panel{display:block;border-width:0;padding:1em 1.4em;background:0}.ui-tooltip{padding:8px;position:absolute;z-index:9999;max-width:300px;-webkit-box-shadow:0 0 5px #aaa;box-shadow:0 0 5px #aaa}body .ui-tooltip{border-width:2px}.ui-widget{font-family:Verdana,Arial,sans-serif;font-size:1.1em}.ui-widget .ui-widget{font-size:1em}.ui-widget input,.ui-widget select,.ui-widget textarea,.ui-widget button{font-family:Verdana,Arial,sans-serif;font-size:1em}.ui-widget-content{border:1px solid #aaa;background:#fff url(images/ui-bg_flat_75_ffffff_40x100.png) 50% 50% repeat-x;color:#222}.ui-widget-content a{color:#222}.ui-widget-header{border:1px solid #aaa;background:#ccc url(images/ui-bg_highlight-soft_75_cccccc_1x100.png) 50% 50% repeat-x;color:#222;font-weight:bold}.ui-widget-header a{color:#222}.ui-state-default,.ui-widget-content .ui-state-default,.ui-widget-header .ui-state-default{border:1px solid #d3d3d3;background:#e6e6e6 url(images/ui-bg_glass_75_e6e6e6_1x400.png) 50% 50% repeat-x;font-weight:normal;color:#555}.ui-state-default a,.ui-state-default a:link,.ui-state-default a:visited{color:#555;text-decoration:none}.ui-state-hover,.ui-widget-content .ui-state-hover,.ui-widget-header .ui-state-hover,.ui-state-focus,.ui-widget-content .ui-state-focus,.ui-widget-header .ui-state-focus{border:1px solid #999;background:#dadada url(images/ui-bg_glass_75_dadada_1x400.png) 50% 50% repeat-x;font-weight:normal;color:#212121}.ui-state-hover a,.ui-state-hover a:hover,.ui-state-hover a:link,.ui-state-hover a:visited{color:#212121;text-decoration:none}.ui-state-active,.ui-widget-content .ui-state-active,.ui-widget-header .ui-state-active{border:1px solid #aaa;background:#fff url(images/ui-bg_glass_65_ffffff_1x400.png) 50% 50% repeat-x;font-weight:normal;color:#212121}.ui-state-active a,.ui-state-active a:link,.ui-state-active a:visited{color:#212121;text-decoration:none}.ui-state-highlight,.ui-widget-content .ui-state-highlight,.ui-widget-header .ui-state-highlight{border:1px solid #fcefa1;background:#fbf9ee url(images/ui-bg_glass_55_fbf9ee_1x400.png) 50% 50% repeat-x;color:#363636}.ui-state-highlight a,.ui-widget-content .ui-state-highlight a,.ui-widget-header 
	.ui-state-highlight a{color:#363636}.ui-state-error,.ui-widget-content .ui-state-error,.ui-widget-header .ui-state-error{border:1px solid #cd0a0a;background:#fef1ec url(images/ui-bg_glass_95_fef1ec_1x400.png) 50% 50% repeat-x;color:#cd0a0a}.ui-state-error a,.ui-widget-content .ui-state-error a,.ui-widget-header .ui-state-error a{color:#cd0a0a}.ui-state-error-text,.ui-widget-content .ui-state-error-text,.ui-widget-header .ui-state-error-text{color:#cd0a0a}.ui-priority-primary,.ui-widget-content .ui-priority-primary,.ui-widget-header .ui-priority-primary{font-weight:bold}.ui-priority-secondary,.ui-widget-content .ui-priority-secondary,.ui-widget-header .ui-priority-secondary{opacity:.7;filter:Alpha(Opacity=70);font-weight:normal}.ui-state-disabled,.ui-widget-content .ui-state-disabled,.ui-widget-header .ui-state-disabled{opacity:.35;filter:Alpha(Opacity=35);background-image:none}.ui-state-disabled .ui-icon{filter:Alpha(Opacity=35)}.ui-icon{width:16px;height:16px}.ui-icon,.ui-widget-content .ui-icon{background-image:url(images/ui-icons_222222_256x240.png)}.ui-widget-header .ui-icon{background-image:url(images/ui-icons_222222_256x240.png)}.ui-state-default .ui-icon{background-image:url(images/ui-icons_888888_256x240.png)}.ui-state-hover .ui-icon,.ui-state-focus .ui-icon{background-image:url(images/ui-icons_454545_256x240.png)}.ui-state-active .ui-icon{background-image:url(images/ui-icons_454545_256x240.png)}.ui-state-highlight .ui-icon{background-image:url(images/ui-icons_2e83ff_256x240.png)}.ui-state-error .ui-icon,.ui-state-error-text .ui-icon{background-image:url(images/ui-icons_cd0a0a_256x240.png)}.ui-icon-blank{background-position:16px 16px}.ui-icon-carat-1-n{background-position:0 0}.ui-icon-carat-1-ne{background-position:-16px 0}.ui-icon-carat-1-e{background-position:-32px 0}.ui-icon-carat-1-se{background-position:-48px 0}.ui-icon-carat-1-s{background-position:-64px 0}.ui-icon-carat-1-sw{background-position:-80px 0}.ui-icon-carat-1-w{background-position:-96px 0}.ui-icon-carat-1-nw{background-position:-112px 0}.ui-icon-carat-2-n-s{background-position:-128px 0}.ui-icon-carat-2-e-w{background-position:-144px 0}.ui-icon-triangle-1-n{background-position:0 -16px}.ui-icon-triangle-1-ne{background-position:-16px -16px}.ui-icon-triangle-1-e{background-position:-32px -16px}.ui-icon-triangle-1-se{background-position:-48px -16px}.ui-icon-triangle-1-s{background-position:-64px -16px}.ui-icon-triangle-1-sw{background-position:-80px -16px}.ui-icon-triangle-1-w{background-position:-96px -16px}.ui-icon-triangle-1-nw{background-position:-112px -16px}.ui-icon-triangle-2-n-s{background-position:-128px -16px}.ui-icon-triangle-2-e-w{background-position:-144px -16px}.ui-icon-arrow-1-n{background-position:0 -32px}.ui-icon-arrow-1-ne{background-position:-16px -32px}.ui-icon-arrow-1-e{background-position:-32px -32px}.ui-icon-arrow-1-se{background-position:-48px -32px}.ui-icon-arrow-1-s{background-position:-64px -32px}.ui-icon-arrow-1-sw{background-position:-80px -32px}.ui-icon-arrow-1-w{background-position:-96px -32px}.ui-icon-arrow-1-nw{background-position:-112px -32px}.ui-icon-arrow-2-n-s{background-position:-128px -32px}.ui-icon-arrow-2-ne-sw{background-position:-144px -32px}.ui-icon-arrow-2-e-w{background-position:-160px -32px}.ui-icon-arrow-2-se-nw{background-position:-176px -32px}.ui-icon-arrowstop-1-n{background-position:-192px -32px}.ui-icon-arrowstop-1-e{background-position:-208px -32px}.ui-icon-arrowstop-1-s{background-position:-224px -32px}.ui-icon-arrowstop-1-w{background-position:-240px -32px}.ui-icon-arrowthick-1-n{background-position:0 -48px}.ui-icon-arrowthick-1-ne{background-position:-16px -48px}.ui-icon-arrowthick-1-e{background-position:-32px -48px}.ui-icon-arrowthick-1-se{background-position:-48px -48px}.ui-icon-arrowthick-1-s{background-position:-64px -48px}.ui-icon-arrowthick-1-sw{background-position:-80px -48px}.ui-icon-arrowthick-1-w{background-position:-96px -48px}.ui-icon-arrowthick-1-nw{background-position:-112px -48px}.ui-icon-arrowthick-2-n-s{background-position:-128px -48px}.ui-icon-arrowthick-2-ne-sw{background-position:-144px -48px}.ui-icon-arrowthick-2-e-w{background-position:-160px -48px}.ui-icon-arrowthick-2-se-nw{background-position:-176px -48px}.ui-icon-arrowthickstop-1-n{background-position:-192px -48px}.ui-icon-arrowthickstop-1-e{background-position:-208px -48px}.ui-icon-arrowthickstop-1-s{background-position:-224px -48px}.ui-icon-arrowthickstop-1-w{background-position:-240px -48px}.ui-icon-arrowreturnthick-1-w{background-position:0 -64px}.ui-icon-arrowreturnthick-1-n{background-position:-16px -64px}.ui-icon-arrowreturnthick-1-e{background-position:-32px -64px}.ui-icon-arrowreturnthick-1-s{background-position:-48px -64px}.ui-icon-arrowreturn-1-w{background-position:-64px -64px}.ui-icon-arrowreturn-1-n{background-position:-80px -64px}.ui-icon-arrowreturn-1-e{background-position:-96px -64px}.ui-icon-arrowreturn-1-s{background-position:-112px -64px}.ui-icon-arrowrefresh-1-w{background-position:-128px -64px}.ui-icon-arrowrefresh-1-n{background-position:-144px -64px}.ui-icon-arrowrefresh-1-e{background-position:-160px -64px}.ui-icon-arrowrefresh-1-s{background-position:-176px -64px}.ui-icon-arrow-4{background-position:0 -80px}.ui-icon-arrow-4-diag{background-position:-16px -80px}.ui-icon-extlink{background-position:-32px -80px}.ui-icon-newwin{background-position:-48px -80px}.ui-icon-refresh{background-position:-64px -80px}.ui-icon-shuffle{background-position:-80px -80px}.ui-icon-transfer-e-w{background-position:-96px -80px}.ui-icon-transferthick-e-w{background-position:-112px -80px}.ui-icon-folder-collapsed{background-position:0 -96px}.ui-icon-folder-open{background-position:-16px -96px}.ui-icon-document{background-position:-32px -96px}.ui-icon-document-b{background-position:-48px -96px}.ui-icon-note{background-position:-64px -96px}.ui-icon-mail-closed{background-position:-80px -96px}.ui-icon-mail-open{background-position:-96px -96px}.ui-icon-suitcase{background-position:-112px -96px}.ui-icon-comment{background-position:-128px -96px}.ui-icon-person{background-position:-144px -96px}.ui-icon-print{background-position:-160px -96px}.ui-icon-trash{background-position:-176px -96px}.ui-icon-locked{background-position:-192px -96px}.ui-icon-unlocked{background-position:-208px -96px}.ui-icon-bookmark{background-position:-224px -96px}.ui-icon-tag{background-position:-240px -96px}.ui-icon-home{background-position:0 -112px}.ui-icon-flag{background-position:-16px -112px}.ui-icon-calendar{background-position:-32px -112px}.ui-icon-cart{background-position:-48px -112px}.ui-icon-pencil{background-position:-64px -112px}.ui-icon-clock{background-position:-80px -112px}.ui-icon-disk{background-position:-96px -112px}.ui-icon-calculator{background-position:-112px -112px}.ui-icon-zoomin{background-position:-128px -112px}.ui-icon-zoomout{background-position:-144px -112px}.ui-icon-search{background-position:-160px -112px}.ui-icon-wrench{background-position:-176px -112px}.ui-icon-gear{background-position:-192px -112px}.ui-icon-heart{background-position:-208px -112px}.ui-icon-star{background-position:-224px -112px}.ui-icon-link{background-position:-240px -112px}.ui-icon-cancel{background-position:0 -128px}.ui-icon-plus{background-position:-16px -128px}.ui-icon-plusthick{background-position:-32px -128px}.ui-icon-minus{background-position:-48px -128px}.ui-icon-minusthick{background-position:-64px -128px}.ui-icon-close{background-position:-80px -128px}.ui-icon-closethick{background-position:-96px -128px}.ui-icon-key{background-position:-112px -128px}.ui-icon-lightbulb{background-position:-128px -128px}.ui-icon-scissors{background-position:-144px -128px}.ui-icon-clipboard{background-position:-160px -128px}.ui-icon-copy{background-position:-176px -128px}.ui-icon-contact{background-position:-192px -128px}.ui-icon-image{background-position:-208px -128px}.ui-icon-video{background-position:-224px -128px}.ui-icon-script{background-position:-240px -128px}.ui-icon-alert{background-position:0 -144px}.ui-icon-info{background-position:-16px -144px}.ui-icon-notice{background-position:-32px -144px}.ui-icon-help{background-position:-48px -144px}.ui-icon-check{background-position:-64px -144px}.ui-icon-bullet{background-position:-80px -144px}.ui-icon-radio-on{background-position:-96px -144px}.ui-icon-radio-off{background-position:-112px -144px}.ui-icon-pin-w{background-position:-128px -144px}.ui-icon-pin-s{background-position:-144px -144px}.ui-icon-play{background-position:0 -160px}.ui-icon-pause{background-position:-16px -160px}.ui-icon-seek-next{background-position:-32px -160px}.ui-icon-seek-prev{background-position:-48px -160px}.ui-icon-seek-end{background-position:-64px -160px}.ui-icon-seek-start{background-position:-80px -160px}.ui-icon-seek-first{background-position:-80px -160px}.ui-icon-stop{background-position:-96px -160px}.ui-icon-eject{background-position:-112px -160px}.ui-icon-volume-off{background-position:-128px -160px}.ui-icon-volume-on{background-position:-144px -160px}.ui-icon-power{background-position:0 -176px}.ui-icon-signal-diag{background-position:-16px -176px}.ui-icon-signal{background-position:-32px -176px}.ui-icon-battery-0{background-position:-48px -176px}.ui-icon-battery-1{background-position:-64px -176px}.ui-icon-battery-2{background-position:-80px -176px}.ui-icon-battery-3{background-position:-96px -176px}.ui-icon-circle-plus{background-position:0 -192px}.ui-icon-circle-minus{background-position:-16px -192px}.ui-icon-circle-close{background-position:-32px -192px}.ui-icon-circle-triangle-e{background-position:-48px -192px}.ui-icon-circle-triangle-s{background-position:-64px -192px}.ui-icon-circle-triangle-w{background-position:-80px -192px}.ui-icon-circle-triangle-n{background-position:-96px -192px}.ui-icon-circle-arrow-e{background-position:-112px -192px}.ui-icon-circle-arrow-s{background-position:-128px -192px}.ui-icon-circle-arrow-w{background-position:-144px -192px}.ui-icon-circle-arrow-n{background-position:-160px -192px}.ui-icon-circle-zoomin{background-position:-176px -192px}.ui-icon-circle-zoomout{background-position:-192px -192px}.ui-icon-circle-check{background-position:-208px -192px}.ui-icon-circlesmall-plus{background-position:0 -208px}.ui-icon-circlesmall-minus{background-position:-16px -208px}.ui-icon-circlesmall-close{background-position:-32px -208px}.ui-icon-squaresmall-plus{background-position:-48px -208px}.ui-icon-squaresmall-minus{background-position:-64px -208px}.ui-icon-squaresmall-close{background-position:-80px -208px}.ui-icon-grip-dotted-vertical{background-position:0 -224px}.ui-icon-grip-dotted-horizontal{background-position:-16px -224px}.ui-icon-grip-solid-vertical{background-position:-32px -224px}.ui-icon-grip-solid-horizontal{background-position:-48px -224px}.ui-icon-gripsmall-diagonal-se{background-position:-64px -224px}.ui-icon-grip-diagonal-se{background-position:-80px -224px}.ui-corner-all,.ui-corner-top,.ui-corner-left,.ui-corner-tl{border-top-left-radius:4px}.ui-corner-all,.ui-corner-top,.ui-corner-right,.ui-corner-tr{border-top-right-radius:4px}.ui-corner-all,.ui-corner-bottom,.ui-corner-left,.ui-corner-bl{border-bottom-left-radius:4px}.ui-corner-all,.ui-corner-bottom,.ui-corner-right,.ui-corner-br{border-bottom-right-radius:4px}.ui-widget-overlay{background:#aaa url(images/ui-bg_flat_0_aaaaaa_40x100.png) 50% 50% repeat-x;opacity:.3;filter:Alpha(Opacity=30)}.ui-widget-shadow{margin:-8px 0 0 -8px;padding:8px;background:#aaa url(images/ui-bg_flat_0_aaaaaa_40x100.png) 50% 50% repeat-x;opacity:.3;filter:Alpha(Opacity=30);border-radius:8px}
	</style>
<?php endif; ?>	
	<?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
?>
<style>
	body {font-family: "Open Sans",sans-serif;}
	body,td,th {font-size:13px;color:#000;}
	fieldset {border:1px solid silver; border-radius:5px; padding:10px}
	h3 {margin:1px; padding:1px; font-size:14px;}
	a {color:#222}
	a:hover{color:gray}
	input[type=text] { width:500px; border-radius:3px; height:17px; font-size:12px; border:1px solid silver;}
	input.readonly {background-color:#efefef;}
	i.small {font-size:11px}

	/* ============================
	COMMON VIEW ELEMENTS*/
	div#content {border:1px solid #CDCDCD;  width:750px; min-height:550px; margin:auto; margin-top:18px; border-radius:5px;  box-shadow: 0 8px 6px -6px #333;}
	div#content-inner {padding:10px 30px; min-height:550px}
	form.content-form {min-height:550px; position:relative; line-height:17px}	
	div.dup-logfile-link {float:right; font-weight:normal; font-size:12px}
	
	/* WIZARD STEPS */
	table.header-wizard {border-top-left-radius:5px; border-top-right-radius:5px; width:100%; box-shadow:0 6px 4px -4px #777;	background-color:#F1F1F1}
	div#dup-wiz {padding:0px; margin:7px 0px 10px 20px; height:20px }
	div#dup-wiz-steps {margin:0px 0px 0px 10px; padding:0px;  clear:both; font-weight:bold;font-size:12px; min-width:250px }
	#dup-wiz span {display:block;float:left; text-align:center; width:15px; margin:3px 4px 0px 0px; line-height:15px; color:#ccc; border:1px solid #CCCCCC; border-radius:4px;}
	/* WIZ-DEFAULT*/
	#dup-wiz a { position:relative; display:block; width:auto; height:24px; margin-right:18px; padding:0px 10px 0px 3px; float:left;  line-height:24px; color:#000; background:#E4E4E4; }
	#dup-wiz a:before { width:0px; height:0px; border-top:12px solid #E4E4E4; border-bottom:12px solid #E4E4E4; border-left:12px solid transparent; position:absolute; content:""; top:0px; left:-12px; }
	#dup-wiz a:after { width:0; height:0; border-top:12px solid transparent; border-bottom:12px solid transparent; border-left:12px solid #E4E4E4; position:absolute; content:""; top:0px; right:-12px; }
	/* WIZ-COMPLETED */
	#dup-wiz .completed-step a {color:#ccc; background:#999;}
	#dup-wiz .completed-step a:before {border-top:12px solid #999; border-bottom:12px solid #999;}
	#dup-wiz .completed-step a:after {border-left:12px solid #999;}
	#dup-wiz .completed-step span {color:#ccc;}
	/* WIZ-ACTIVE */
	#dup-wiz .active-step a {color:#fff; background:#999;}
	#dup-wiz .active-step a:before {border-top:12px solid #999; border-bottom:12px solid #999;}
	#dup-wiz .active-step a:after {border-left:12px solid #999;}
	#dup-wiz .active-step span {color:#fff;}
	
	/*Help */
	select#dup-hlp-lnk {border-radius:3px; font-size:11px; margin:3px 5px 0px 0px; background-color:#efefef; border:1px solid silver}
	div.dup-help-page {padding:5px 0px 0px 5px}
	div.dup-help-page fieldset {margin-bottom:25px}
	div#dup-main-help h3 {background-color:#dfdfdf; border:1px solid silver; border-radius:5px; padding:3px; margin-bottom:8px}

	div#progress-area {padding:5px; margin:150px 0px 0px 0px; text-align:center;}
	div#ajaxerr-data {padding:5px; height:350px; width:99%; border:1px solid silver; border-radius:5px; background-color:#efefef; font-size:12px; overflow-y:scroll}
	div.title-header {padding:2px; border-bottom:1px solid silver; font-weight:bold; margin-bottom:5px;}
	
	/*BOXES: Expandable sections */
	div.dup-box {padding:0px; display: block; background-color: #fff; border: 1px solid #e5e5e5; box-shadow: 0 1px 1px rgba(0,0,0,.04);}
	div.dup-box-title {font-size: 14px; padding: 5px 0px 0px 10px; font-weight: bold; cursor: pointer; height:23px; margin:0px; background-color:#F9F9F9}
	div.dup-box-title:hover {background-color: #efefef;}
	div.dup-box-arrow {text-decoration:none!important; float:right; width:27px; height:30px; font-size:16px; cursor:pointer; padding:1px 0px 0px 0px; white-space: nowrap}
	div.dup-box-panel {padding:10px 15px 10px 15px;  border-top:1px solid #EEEEEE; margin:-1px 0px 0px 0px; background-color:#F9F9F9;   box-shadow: 0 8px 6px -6px #999;}

	/* ============================
	STEP 1 VIEW */
	i#dup-step1-sys-req-msg {font-weight:normal; display:block; padding:0px 0px 0px 20px;}
	div.circle-pass, div.circle-fail {display:block;width:13px;height:13px;border-radius:50px;font-size:20px;color:#fff;line-height:100px;text-align:center;text-decoration:none;box-shadow:1px 1px 2px #000;background:#207D1D;opacity:0.95; display:inline-block;}
	div.circle-fail {background:#9A0D1D !important;}
	select#logging {font-size:11px}
	table.dup-step1-inputs {width: 100%; border: 0px;}
	table.dup-step1-inputs td{white-space:nowrap; padding:2px;}
	table.dup-step1-inputs td:first-child{width:125px}
	div.dup-step1-modes {padding:0px 15px 0px 0px;}
	div#dup-step1-dbconn {margin:auto; text-align:center; margin:15px 0px 20px 0px}
	input#dup-step1-dbconn-btn {font-size:11px; height:20px; border:1px solid gray; border-radius:3px; cursor:pointer}
	div.dup-db-test label{display: inline-block; width: 150px; font-weight: bold; white-space: nowrap}
	div.dup-db-test small{display:block; margin:0px 0px 15px 0px; border-bottom: 1px solid silver; font-style: italic; color:#777}
	div#dbconn-test-msg {font-size:12px}
	
	/*Warning Area and Message */
	div#dup-step1-warning {margin-top:40px;padding:5px;font-size:11px; color:gray; line-height:12px;font-style:italic; overflow-y:scroll; height:75px; border:1px solid #dfdfdf; background-color:#fff; border-radius:3px}
	div#dup-step1-warning-check {padding:5px; font-size:12px; font-weight:normal; font-style:italic;}
	div#dup-step1-warning-emptydb {display:none; color:#AF2222; margin:0px 0px 0px 20px}
	div#dup-step1-tryagain {padding-top:50px; text-align:center; width:100%; font-size:16px; color:#444; font-weight:bold;}

	/*Dialog*/
	div#dup-step1-dialog-data {height:90%; font-size:11px; padding:5px; line-height:16px; }
	td.dup-step1-dialog-data-details {padding:0px 0px 0px 30px; border-radius:4px; line-height:14px; font-size:11px; display:none}
	td.dup-step1-dialog-data-details b {width:50px;display:inline-block}
	.dup-pass {display:inline-block; color:green;}
	.dup-ok {display:inline-block; color:#5860C7;}
	.dup-fail {display:inline-block; color:#AF0000;}
	hr.dup-dots { border:none; border-top:1px dotted silver; height:1px; width:100%;}
	div.dup-ui-error {padding-top:2px; font-size:14px}
	div.help {color:#555; font-style:italic; font-size:11px}

	/* ============================
	STEP 2 VIEW */
	table.table-inputs-step2{width: 100%; border: 0px;}
	table.table-inputs-step2 td{white-space:nowrap; padding:2px;}
	div#dup-step2-adv-opts {margin-top:5px; }
	div.dup-step2-allnonelinks {font-size:11px; float:right;}

	/* ============================
	STEP 3 VIEW */
	div.dup-step3-final-msg {height:110px; border:1px solid #CDCDCD; padding:8px;font-size:12px; border-radius:5px;box-shadow:0 4px 2px -2px #777;}
	div.dup-step3-final-title {color:#BE2323;}
	div.dup-step3-connect {font-size:12px; text-align:center; font-style:italic; position:absolute; bottom:10px; padding:10px; width:100%; margin-top:20px}
	div#dup-step3-dialog{font-size:12px}
	table.dup-step3-report-results,
	table.dup-step3-report-errs {border-collapse:collapse; border:1px solid #dfdfdf; }
	table.dup-step3-report-errs  td {text-align:center; width:33%}
	table.dup-step3-report-results th, table.dup-step3-report-errs th {background-color:#efefef; padding:0px; font-size:12px; padding:0px}
	table.dup-step3-report-results td, table.dup-step3-report-errs td {padding:0px; white-space:nowrap; border:1px solid #dfdfdf; text-align:center; font-size:11px}
	table.dup-step3-report-results td:first-child {text-align:left; font-weight:bold; padding-left:3px}

	div.dup-step3-err-title a {}
	div.dup-step3-err-msg {padding:8px;  display:none; border:1px dashed #999; margin:10px 0px 20px 0px; border-radius:5px;}
	div.dup-step3-err-msg div.content{padding:5px; font-size:11px; line-height:17px; max-height:125px; overflow-y:scroll; border:1px solid silver; margin:3px;  }
	div.dup-step3-err-msg div.info{padding:2px; background-color:#FCFEC5; border:1px solid silver; border-radius:5px; font-size:11px; line-height:16px }
	table.dup-step3-final-step {width:100%;}
	table.dup-step3-final-step td {padding: 5px 15px 5px 5px}
	table.dup-step3-final-step td:first-child {white-space: nowrap; font-weight: bold}
	div.dup-step3-go-back {border-bottom: 1px dotted #dfdfdf; border-top: 1px dotted #dfdfdf; margin: auto; text-align: center}

	/* ============================
	BUTTONS */	
	div.dup-footer-buttons {position:absolute; bottom:10px; padding:10px; width:100%; text-align:right;}
	div.dup-footer-buttons  input, button {
		color:#000; font-size:12px; border-radius:5px;	padding:6px 8px 4px 8px; border:1px solid #999;
		background-color: #F1F1F1;
		background-image: -ms-linear-gradient(top, #F9F9F9, #ECECEC);
		background-image: -moz-linear-gradient(top, #F9F9F9, #ECECEC);
		background-image: linear-gradient(top, #F9F9F9, #ECECEC);
	}
	div.dup-footer-buttons input[disabled=disabled]{background-color:#F4F4F4; color:silver; border:1px solid silver;}
	div.dup-footer-buttons  input, button {cursor:pointer; border:1px solid #000; }


	/*!
	 * password indicator
	 */
	.top_testresult{font-weight: bold;	font-size:11px; color:#222;	padding:1px 1px 1px 4px; margin:4px 0px 0px 0px; width:495px; dislay:inline-block}
	.top_testresult span{margin:0;}
	.top_shortPass{background:#edabab; border:1px solid #bc0000;display:block;}
	.top_badPass{background:#edabab;border:1px solid #bc0000;display:block;}
	.top_goodPass{background:#ffffe0; border:1px solid #e6db55;	display:block;}
	.top_strongPass{background:#d3edab;	border:1px solid #73bc00; display:block;}

	/* ============================
	CUSTOME OVERIDE */
	/*Hide X button on close dialog*/
	div.ui-dialog-titlebar button.ui-dialog-titlebar-close {display:none !important}
	
	/*================================================
	PARSLEY:Overrides*/
	input.parsley-error, textarea.parsley-error {
	  color:#B94A48 !important;
	  background-color:#F2DEDE !important;
	  border:1px solid #EED3D7 !important;
	}
	ul.parsley-error-list {margin:1px 0px 0px -40px; list-style-type: none; font-size:10px}
</style>	
	<?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
?>
<!-- ========================================
JQUERY ASSETS -->
<?php if(DupUtil::is_url_active("ajax.googleapis.com", 443) ): ?>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
<?php else: ?>
	<script type="text/javascript">
	/*! jQuery v1.10.2 | (c) 2005, 2013 jQuery Foundation, Inc. | jquery.org/license
	//@ sourceMappingURL=jquery.min.map
	*/
	(function(e,t){var n,r,i=typeof t,o=e.location,a=e.document,s=a.documentElement,l=e.jQuery,u=e.$,c={},p=[],f="1.10.2",d=p.concat,h=p.push,g=p.slice,m=p.indexOf,y=c.toString,v=c.hasOwnProperty,b=f.trim,x=function(e,t){return new x.fn.init(e,t,r)},w=/[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,T=/\S+/g,C=/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,N=/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]*))$/,k=/^<(\w+)\s*\/?>(?:<\/\1>|)$/,E=/^[\],:{}\s]*$/,S=/(?:^|:|,)(?:\s*\[)+/g,A=/\\(?:["\\\/bfnrt]|u[\da-fA-F]{4})/g,j=/"[^"\\\r\n]*"|true|false|null|-?(?:\d+\.|)\d+(?:[eE][+-]?\d+|)/g,D=/^-ms-/,L=/-([\da-z])/gi,H=function(e,t){return t.toUpperCase()},q=function(e){(a.addEventListener||"load"===e.type||"complete"===a.readyState)&&(_(),x.ready())},_=function(){a.addEventListener?(a.removeEventListener("DOMContentLoaded",q,!1),e.removeEventListener("load",q,!1)):(a.detachEvent("onreadystatechange",q),e.detachEvent("onload",q))};x.fn=x.prototype={jquery:f,constructor:x,init:function(e,n,r){var i,o;if(!e)return this;if("string"==typeof e){if(i="<"===e.charAt(0)&&">"===e.charAt(e.length-1)&&e.length>=3?[null,e,null]:N.exec(e),!i||!i[1]&&n)return!n||n.jquery?(n||r).find(e):this.constructor(n).find(e);if(i[1]){if(n=n instanceof x?n[0]:n,x.merge(this,x.parseHTML(i[1],n&&n.nodeType?n.ownerDocument||n:a,!0)),k.test(i[1])&&x.isPlainObject(n))for(i in n)x.isFunction(this[i])?this[i](n[i]):this.attr(i,n[i]);return this}if(o=a.getElementById(i[2]),o&&o.parentNode){if(o.id!==i[2])return r.find(e);this.length=1,this[0]=o}return this.context=a,this.selector=e,this}return e.nodeType?(this.context=this[0]=e,this.length=1,this):x.isFunction(e)?r.ready(e):(e.selector!==t&&(this.selector=e.selector,this.context=e.context),x.makeArray(e,this))},selector:"",length:0,toArray:function(){return g.call(this)},get:function(e){return null==e?this.toArray():0>e?this[this.length+e]:this[e]},pushStack:function(e){var t=x.merge(this.constructor(),e);return t.prevObject=this,t.context=this.context,t},each:function(e,t){return x.each(this,e,t)},ready:function(e){return x.ready.promise().done(e),this},slice:function(){return this.pushStack(g.apply(this,arguments))},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},eq:function(e){var t=this.length,n=+e+(0>e?t:0);return this.pushStack(n>=0&&t>n?[this[n]]:[])},map:function(e){return this.pushStack(x.map(this,function(t,n){return e.call(t,n,t)}))},end:function(){return this.prevObject||this.constructor(null)},push:h,sort:[].sort,splice:[].splice},x.fn.init.prototype=x.fn,x.extend=x.fn.extend=function(){var e,n,r,i,o,a,s=arguments[0]||{},l=1,u=arguments.length,c=!1;for("boolean"==typeof s&&(c=s,s=arguments[1]||{},l=2),"object"==typeof s||x.isFunction(s)||(s={}),u===l&&(s=this,--l);u>l;l++)if(null!=(o=arguments[l]))for(i in o)e=s[i],r=o[i],s!==r&&(c&&r&&(x.isPlainObject(r)||(n=x.isArray(r)))?(n?(n=!1,a=e&&x.isArray(e)?e:[]):a=e&&x.isPlainObject(e)?e:{},s[i]=x.extend(c,a,r)):r!==t&&(s[i]=r));return s},x.extend({expando:"jQuery"+(f+Math.random()).replace(/\D/g,""),noConflict:function(t){return e.$===x&&(e.$=u),t&&e.jQuery===x&&(e.jQuery=l),x},isReady:!1,readyWait:1,holdReady:function(e){e?x.readyWait++:x.ready(!0)},ready:function(e){if(e===!0?!--x.readyWait:!x.isReady){if(!a.body)return setTimeout(x.ready);x.isReady=!0,e!==!0&&--x.readyWait>0||(n.resolveWith(a,[x]),x.fn.trigger&&x(a).trigger("ready").off("ready"))}},isFunction:function(e){return"function"===x.type(e)},isArray:Array.isArray||function(e){return"array"===x.type(e)},isWindow:function(e){return null!=e&&e==e.window},isNumeric:function(e){return!isNaN(parseFloat(e))&&isFinite(e)},type:function(e){return null==e?e+"":"object"==typeof e||"function"==typeof e?c[y.call(e)]||"object":typeof e},isPlainObject:function(e){var n;if(!e||"object"!==x.type(e)||e.nodeType||x.isWindow(e))return!1;try{if(e.constructor&&!v.call(e,"constructor")&&!v.call(e.constructor.prototype,"isPrototypeOf"))return!1}catch(r){return!1}if(x.support.ownLast)for(n in e)return v.call(e,n);for(n in e);return n===t||v.call(e,n)},isEmptyObject:function(e){var t;for(t in e)return!1;return!0},error:function(e){throw Error(e)},parseHTML:function(e,t,n){if(!e||"string"!=typeof e)return null;"boolean"==typeof t&&(n=t,t=!1),t=t||a;var r=k.exec(e),i=!n&&[];return r?[t.createElement(r[1])]:(r=x.buildFragment([e],t,i),i&&x(i).remove(),x.merge([],r.childNodes))},parseJSON:function(n){return e.JSON&&e.JSON.parse?e.JSON.parse(n):null===n?n:"string"==typeof n&&(n=x.trim(n),n&&E.test(n.replace(A,"@").replace(j,"]").replace(S,"")))?Function("return "+n)():(x.error("Invalid JSON: "+n),t)},parseXML:function(n){var r,i;if(!n||"string"!=typeof n)return null;try{e.DOMParser?(i=new DOMParser,r=i.parseFromString(n,"text/xml")):(r=new ActiveXObject("Microsoft.XMLDOM"),r.async="false",r.loadXML(n))}catch(o){r=t}return r&&r.documentElement&&!r.getElementsByTagName("parsererror").length||x.error("Invalid XML: "+n),r},noop:function(){},globalEval:function(t){t&&x.trim(t)&&(e.execScript||function(t){e.eval.call(e,t)})(t)},camelCase:function(e){return e.replace(D,"ms-").replace(L,H)},nodeName:function(e,t){return e.nodeName&&e.nodeName.toLowerCase()===t.toLowerCase()},each:function(e,t,n){var r,i=0,o=e.length,a=M(e);if(n){if(a){for(;o>i;i++)if(r=t.apply(e[i],n),r===!1)break}else for(i in e)if(r=t.apply(e[i],n),r===!1)break}else if(a){for(;o>i;i++)if(r=t.call(e[i],i,e[i]),r===!1)break}else for(i in e)if(r=t.call(e[i],i,e[i]),r===!1)break;return e},trim:b&&!b.call("\ufeff\u00a0")?function(e){return null==e?"":b.call(e)}:function(e){return null==e?"":(e+"").replace(C,"")},makeArray:function(e,t){var n=t||[];return null!=e&&(M(Object(e))?x.merge(n,"string"==typeof e?[e]:e):h.call(n,e)),n},inArray:function(e,t,n){var r;if(t){if(m)return m.call(t,e,n);for(r=t.length,n=n?0>n?Math.max(0,r+n):n:0;r>n;n++)if(n in t&&t[n]===e)return n}return-1},merge:function(e,n){var r=n.length,i=e.length,o=0;if("number"==typeof r)for(;r>o;o++)e[i++]=n[o];else while(n[o]!==t)e[i++]=n[o++];return e.length=i,e},grep:function(e,t,n){var r,i=[],o=0,a=e.length;for(n=!!n;a>o;o++)r=!!t(e[o],o),n!==r&&i.push(e[o]);return i},map:function(e,t,n){var r,i=0,o=e.length,a=M(e),s=[];if(a)for(;o>i;i++)r=t(e[i],i,n),null!=r&&(s[s.length]=r);else for(i in e)r=t(e[i],i,n),null!=r&&(s[s.length]=r);return d.apply([],s)},guid:1,proxy:function(e,n){var r,i,o;return"string"==typeof n&&(o=e[n],n=e,e=o),x.isFunction(e)?(r=g.call(arguments,2),i=function(){return e.apply(n||this,r.concat(g.call(arguments)))},i.guid=e.guid=e.guid||x.guid++,i):t},access:function(e,n,r,i,o,a,s){var l=0,u=e.length,c=null==r;if("object"===x.type(r)){o=!0;for(l in r)x.access(e,n,l,r[l],!0,a,s)}else if(i!==t&&(o=!0,x.isFunction(i)||(s=!0),c&&(s?(n.call(e,i),n=null):(c=n,n=function(e,t,n){return c.call(x(e),n)})),n))for(;u>l;l++)n(e[l],r,s?i:i.call(e[l],l,n(e[l],r)));return o?e:c?n.call(e):u?n(e[0],r):a},now:function(){return(new Date).getTime()},swap:function(e,t,n,r){var i,o,a={};for(o in t)a[o]=e.style[o],e.style[o]=t[o];i=n.apply(e,r||[]);for(o in t)e.style[o]=a[o];return i}}),x.ready.promise=function(t){if(!n)if(n=x.Deferred(),"complete"===a.readyState)setTimeout(x.ready);else if(a.addEventListener)a.addEventListener("DOMContentLoaded",q,!1),e.addEventListener("load",q,!1);else{a.attachEvent("onreadystatechange",q),e.attachEvent("onload",q);var r=!1;try{r=null==e.frameElement&&a.documentElement}catch(i){}r&&r.doScroll&&function o(){if(!x.isReady){try{r.doScroll("left")}catch(e){return setTimeout(o,50)}_(),x.ready()}}()}return n.promise(t)},x.each("Boolean Number String Function Array Date RegExp Object Error".split(" "),function(e,t){c["[object "+t+"]"]=t.toLowerCase()});function M(e){var t=e.length,n=x.type(e);return x.isWindow(e)?!1:1===e.nodeType&&t?!0:"array"===n||"function"!==n&&(0===t||"number"==typeof t&&t>0&&t-1 in e)}r=x(a),function(e,t){var n,r,i,o,a,s,l,u,c,p,f,d,h,g,m,y,v,b="sizzle"+-new Date,w=e.document,T=0,C=0,N=st(),k=st(),E=st(),S=!1,A=function(e,t){return e===t?(S=!0,0):0},j=typeof t,D=1<<31,L={}.hasOwnProperty,H=[],q=H.pop,_=H.push,M=H.push,O=H.slice,F=H.indexOf||function(e){var t=0,n=this.length;for(;n>t;t++)if(this[t]===e)return t;return-1},B="checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",P="[\\x20\\t\\r\\n\\f]",R="(?:\\\\.|[\\w-]|[^\\x00-\\xa0])+",W=R.replace("w","w#"),$="\\["+P+"*("+R+")"+P+"*(?:([*^$|!~]?=)"+P+"*(?:(['\"])((?:\\\\.|[^\\\\])*?)\\3|("+W+")|)|)"+P+"*\\]",I=":("+R+")(?:\\(((['\"])((?:\\\\.|[^\\\\])*?)\\3|((?:\\\\.|[^\\\\()[\\]]|"+$.replace(3,8)+")*)|.*)\\)|)",z=RegExp("^"+P+"+|((?:^|[^\\\\])(?:\\\\.)*)"+P+"+$","g"),X=RegExp("^"+P+"*,"+P+"*"),U=RegExp("^"+P+"*([>+~]|"+P+")"+P+"*"),V=RegExp(P+"*[+~]"),Y=RegExp("="+P+"*([^\\]'\"]*)"+P+"*\\]","g"),J=RegExp(I),G=RegExp("^"+W+"$"),Q={ID:RegExp("^#("+R+")"),CLASS:RegExp("^\\.("+R+")"),TAG:RegExp("^("+R.replace("w","w*")+")"),ATTR:RegExp("^"+$),PSEUDO:RegExp("^"+I),CHILD:RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\("+P+"*(even|odd|(([+-]|)(\\d*)n|)"+P+"*(?:([+-]|)"+P+"*(\\d+)|))"+P+"*\\)|)","i"),bool:RegExp("^(?:"+B+")$","i"),needsContext:RegExp("^"+P+"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\("+P+"*((?:-\\d)?\\d*)"+P+"*\\)|)(?=[^-]|$)","i")},K=/^[^{]+\{\s*\[native \w/,Z=/^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,et=/^(?:input|select|textarea|button)$/i,tt=/^h\d$/i,nt=/'|\\/g,rt=RegExp("\\\\([\\da-f]{1,6}"+P+"?|("+P+")|.)","ig"),it=function(e,t,n){var r="0x"+t-65536;return r!==r||n?t:0>r?String.fromCharCode(r+65536):String.fromCharCode(55296|r>>10,56320|1023&r)};try{M.apply(H=O.call(w.childNodes),w.childNodes),H[w.childNodes.length].nodeType}catch(ot){M={apply:H.length?function(e,t){_.apply(e,O.call(t))}:function(e,t){var n=e.length,r=0;while(e[n++]=t[r++]);e.length=n-1}}}function at(e,t,n,i){var o,a,s,l,u,c,d,m,y,x;if((t?t.ownerDocument||t:w)!==f&&p(t),t=t||f,n=n||[],!e||"string"!=typeof e)return n;if(1!==(l=t.nodeType)&&9!==l)return[];if(h&&!i){if(o=Z.exec(e))if(s=o[1]){if(9===l){if(a=t.getElementById(s),!a||!a.parentNode)return n;if(a.id===s)return n.push(a),n}else if(t.ownerDocument&&(a=t.ownerDocument.getElementById(s))&&v(t,a)&&a.id===s)return n.push(a),n}else{if(o[2])return M.apply(n,t.getElementsByTagName(e)),n;if((s=o[3])&&r.getElementsByClassName&&t.getElementsByClassName)return M.apply(n,t.getElementsByClassName(s)),n}if(r.qsa&&(!g||!g.test(e))){if(m=d=b,y=t,x=9===l&&e,1===l&&"object"!==t.nodeName.toLowerCase()){c=mt(e),(d=t.getAttribute("id"))?m=d.replace(nt,"\\$&"):t.setAttribute("id",m),m="[id='"+m+"'] ",u=c.length;while(u--)c[u]=m+yt(c[u]);y=V.test(e)&&t.parentNode||t,x=c.join(",")}if(x)try{return M.apply(n,y.querySelectorAll(x)),n}catch(T){}finally{d||t.removeAttribute("id")}}}return kt(e.replace(z,"$1"),t,n,i)}function st(){var e=[];function t(n,r){return e.push(n+=" ")>o.cacheLength&&delete t[e.shift()],t[n]=r}return t}function lt(e){return e[b]=!0,e}function ut(e){var t=f.createElement("div");try{return!!e(t)}catch(n){return!1}finally{t.parentNode&&t.parentNode.removeChild(t),t=null}}function ct(e,t){var n=e.split("|"),r=e.length;while(r--)o.attrHandle[n[r]]=t}function pt(e,t){var n=t&&e,r=n&&1===e.nodeType&&1===t.nodeType&&(~t.sourceIndex||D)-(~e.sourceIndex||D);if(r)return r;if(n)while(n=n.nextSibling)if(n===t)return-1;return e?1:-1}function ft(e){return function(t){var n=t.nodeName.toLowerCase();return"input"===n&&t.type===e}}function dt(e){return function(t){var n=t.nodeName.toLowerCase();return("input"===n||"button"===n)&&t.type===e}}function ht(e){return lt(function(t){return t=+t,lt(function(n,r){var i,o=e([],n.length,t),a=o.length;while(a--)n[i=o[a]]&&(n[i]=!(r[i]=n[i]))})})}s=at.isXML=function(e){var t=e&&(e.ownerDocument||e).documentElement;return t?"HTML"!==t.nodeName:!1},r=at.support={},p=at.setDocument=function(e){var n=e?e.ownerDocument||e:w,i=n.defaultView;return n!==f&&9===n.nodeType&&n.documentElement?(f=n,d=n.documentElement,h=!s(n),i&&i.attachEvent&&i!==i.top&&i.attachEvent("onbeforeunload",function(){p()}),r.attributes=ut(function(e){return e.className="i",!e.getAttribute("className")}),r.getElementsByTagName=ut(function(e){return e.appendChild(n.createComment("")),!e.getElementsByTagName("*").length}),r.getElementsByClassName=ut(function(e){return e.innerHTML="<div class='a'></div><div class='a i'></div>",e.firstChild.className="i",2===e.getElementsByClassName("i").length}),r.getById=ut(function(e){return d.appendChild(e).id=b,!n.getElementsByName||!n.getElementsByName(b).length}),r.getById?(o.find.ID=function(e,t){if(typeof t.getElementById!==j&&h){var n=t.getElementById(e);return n&&n.parentNode?[n]:[]}},o.filter.ID=function(e){var t=e.replace(rt,it);return function(e){return e.getAttribute("id")===t}}):(delete o.find.ID,o.filter.ID=function(e){var t=e.replace(rt,it);return function(e){var n=typeof e.getAttributeNode!==j&&e.getAttributeNode("id");return n&&n.value===t}}),o.find.TAG=r.getElementsByTagName?function(e,n){return typeof n.getElementsByTagName!==j?n.getElementsByTagName(e):t}:function(e,t){var n,r=[],i=0,o=t.getElementsByTagName(e);if("*"===e){while(n=o[i++])1===n.nodeType&&r.push(n);return r}return o},o.find.CLASS=r.getElementsByClassName&&function(e,n){return typeof n.getElementsByClassName!==j&&h?n.getElementsByClassName(e):t},m=[],g=[],(r.qsa=K.test(n.querySelectorAll))&&(ut(function(e){e.innerHTML="<select><option selected=''></option></select>",e.querySelectorAll("[selected]").length||g.push("\\["+P+"*(?:value|"+B+")"),e.querySelectorAll(":checked").length||g.push(":checked")}),ut(function(e){var t=n.createElement("input");t.setAttribute("type","hidden"),e.appendChild(t).setAttribute("t",""),e.querySelectorAll("[t^='']").length&&g.push("[*^$]="+P+"*(?:''|\"\")"),e.querySelectorAll(":enabled").length||g.push(":enabled",":disabled"),e.querySelectorAll("*,:x"),g.push(",.*:")})),(r.matchesSelector=K.test(y=d.webkitMatchesSelector||d.mozMatchesSelector||d.oMatchesSelector||d.msMatchesSelector))&&ut(function(e){r.disconnectedMatch=y.call(e,"div"),y.call(e,"[s!='']:x"),m.push("!=",I)}),g=g.length&&RegExp(g.join("|")),m=m.length&&RegExp(m.join("|")),v=K.test(d.contains)||d.compareDocumentPosition?function(e,t){var n=9===e.nodeType?e.documentElement:e,r=t&&t.parentNode;return e===r||!(!r||1!==r.nodeType||!(n.contains?n.contains(r):e.compareDocumentPosition&&16&e.compareDocumentPosition(r)))}:function(e,t){if(t)while(t=t.parentNode)if(t===e)return!0;return!1},A=d.compareDocumentPosition?function(e,t){if(e===t)return S=!0,0;var i=t.compareDocumentPosition&&e.compareDocumentPosition&&e.compareDocumentPosition(t);return i?1&i||!r.sortDetached&&t.compareDocumentPosition(e)===i?e===n||v(w,e)?-1:t===n||v(w,t)?1:c?F.call(c,e)-F.call(c,t):0:4&i?-1:1:e.compareDocumentPosition?-1:1}:function(e,t){var r,i=0,o=e.parentNode,a=t.parentNode,s=[e],l=[t];if(e===t)return S=!0,0;if(!o||!a)return e===n?-1:t===n?1:o?-1:a?1:c?F.call(c,e)-F.call(c,t):0;if(o===a)return pt(e,t);r=e;while(r=r.parentNode)s.unshift(r);r=t;while(r=r.parentNode)l.unshift(r);while(s[i]===l[i])i++;return i?pt(s[i],l[i]):s[i]===w?-1:l[i]===w?1:0},n):f},at.matches=function(e,t){return at(e,null,null,t)},at.matchesSelector=function(e,t){if((e.ownerDocument||e)!==f&&p(e),t=t.replace(Y,"='$1']"),!(!r.matchesSelector||!h||m&&m.test(t)||g&&g.test(t)))try{var n=y.call(e,t);if(n||r.disconnectedMatch||e.document&&11!==e.document.nodeType)return n}catch(i){}return at(t,f,null,[e]).length>0},at.contains=function(e,t){return(e.ownerDocument||e)!==f&&p(e),v(e,t)},at.attr=function(e,n){(e.ownerDocument||e)!==f&&p(e);var i=o.attrHandle[n.toLowerCase()],a=i&&L.call(o.attrHandle,n.toLowerCase())?i(e,n,!h):t;return a===t?r.attributes||!h?e.getAttribute(n):(a=e.getAttributeNode(n))&&a.specified?a.value:null:a},at.error=function(e){throw Error("Syntax error, unrecognized expression: "+e)},at.uniqueSort=function(e){var t,n=[],i=0,o=0;if(S=!r.detectDuplicates,c=!r.sortStable&&e.slice(0),e.sort(A),S){while(t=e[o++])t===e[o]&&(i=n.push(o));while(i--)e.splice(n[i],1)}return e},a=at.getText=function(e){var t,n="",r=0,i=e.nodeType;if(i){if(1===i||9===i||11===i){if("string"==typeof e.textContent)return e.textContent;for(e=e.firstChild;e;e=e.nextSibling)n+=a(e)}else if(3===i||4===i)return e.nodeValue}else for(;t=e[r];r++)n+=a(t);return n},o=at.selectors={cacheLength:50,createPseudo:lt,match:Q,attrHandle:{},find:{},relative:{">":{dir:"parentNode",first:!0}," ":{dir:"parentNode"},"+":{dir:"previousSibling",first:!0},"~":{dir:"previousSibling"}},preFilter:{ATTR:function(e){return e[1]=e[1].replace(rt,it),e[3]=(e[4]||e[5]||"").replace(rt,it),"~="===e[2]&&(e[3]=" "+e[3]+" "),e.slice(0,4)},CHILD:function(e){return e[1]=e[1].toLowerCase(),"nth"===e[1].slice(0,3)?(e[3]||at.error(e[0]),e[4]=+(e[4]?e[5]+(e[6]||1):2*("even"===e[3]||"odd"===e[3])),e[5]=+(e[7]+e[8]||"odd"===e[3])):e[3]&&at.error(e[0]),e},PSEUDO:function(e){var n,r=!e[5]&&e[2];return Q.CHILD.test(e[0])?null:(e[3]&&e[4]!==t?e[2]=e[4]:r&&J.test(r)&&(n=mt(r,!0))&&(n=r.indexOf(")",r.length-n)-r.length)&&(e[0]=e[0].slice(0,n),e[2]=r.slice(0,n)),e.slice(0,3))}},filter:{TAG:function(e){var t=e.replace(rt,it).toLowerCase();return"*"===e?function(){return!0}:function(e){return e.nodeName&&e.nodeName.toLowerCase()===t}},CLASS:function(e){var t=N[e+" "];return t||(t=RegExp("(^|"+P+")"+e+"("+P+"|$)"))&&N(e,function(e){return t.test("string"==typeof e.className&&e.className||typeof e.getAttribute!==j&&e.getAttribute("class")||"")})},ATTR:function(e,t,n){return function(r){var i=at.attr(r,e);return null==i?"!="===t:t?(i+="","="===t?i===n:"!="===t?i!==n:"^="===t?n&&0===i.indexOf(n):"*="===t?n&&i.indexOf(n)>-1:"$="===t?n&&i.slice(-n.length)===n:"~="===t?(" "+i+" ").indexOf(n)>-1:"|="===t?i===n||i.slice(0,n.length+1)===n+"-":!1):!0}},CHILD:function(e,t,n,r,i){var o="nth"!==e.slice(0,3),a="last"!==e.slice(-4),s="of-type"===t;return 1===r&&0===i?function(e){return!!e.parentNode}:function(t,n,l){var u,c,p,f,d,h,g=o!==a?"nextSibling":"previousSibling",m=t.parentNode,y=s&&t.nodeName.toLowerCase(),v=!l&&!s;if(m){if(o){while(g){p=t;while(p=p[g])if(s?p.nodeName.toLowerCase()===y:1===p.nodeType)return!1;h=g="only"===e&&!h&&"nextSibling"}return!0}if(h=[a?m.firstChild:m.lastChild],a&&v){c=m[b]||(m[b]={}),u=c[e]||[],d=u[0]===T&&u[1],f=u[0]===T&&u[2],p=d&&m.childNodes[d];while(p=++d&&p&&p[g]||(f=d=0)||h.pop())if(1===p.nodeType&&++f&&p===t){c[e]=[T,d,f];break}}else if(v&&(u=(t[b]||(t[b]={}))[e])&&u[0]===T)f=u[1];else while(p=++d&&p&&p[g]||(f=d=0)||h.pop())if((s?p.nodeName.toLowerCase()===y:1===p.nodeType)&&++f&&(v&&((p[b]||(p[b]={}))[e]=[T,f]),p===t))break;return f-=i,f===r||0===f%r&&f/r>=0}}},PSEUDO:function(e,t){var n,r=o.pseudos[e]||o.setFilters[e.toLowerCase()]||at.error("unsupported pseudo: "+e);return r[b]?r(t):r.length>1?(n=[e,e,"",t],o.setFilters.hasOwnProperty(e.toLowerCase())?lt(function(e,n){var i,o=r(e,t),a=o.length;while(a--)i=F.call(e,o[a]),e[i]=!(n[i]=o[a])}):function(e){return r(e,0,n)}):r}},pseudos:{not:lt(function(e){var t=[],n=[],r=l(e.replace(z,"$1"));return r[b]?lt(function(e,t,n,i){var o,a=r(e,null,i,[]),s=e.length;while(s--)(o=a[s])&&(e[s]=!(t[s]=o))}):function(e,i,o){return t[0]=e,r(t,null,o,n),!n.pop()}}),has:lt(function(e){return function(t){return at(e,t).length>0}}),contains:lt(function(e){return function(t){return(t.textContent||t.innerText||a(t)).indexOf(e)>-1}}),lang:lt(function(e){return G.test(e||"")||at.error("unsupported lang: "+e),e=e.replace(rt,it).toLowerCase(),function(t){var n;do if(n=h?t.lang:t.getAttribute("xml:lang")||t.getAttribute("lang"))return n=n.toLowerCase(),n===e||0===n.indexOf(e+"-");while((t=t.parentNode)&&1===t.nodeType);return!1}}),target:function(t){var n=e.location&&e.location.hash;return n&&n.slice(1)===t.id},root:function(e){return e===d},focus:function(e){return e===f.activeElement&&(!f.hasFocus||f.hasFocus())&&!!(e.type||e.href||~e.tabIndex)},enabled:function(e){return e.disabled===!1},disabled:function(e){return e.disabled===!0},checked:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&!!e.checked||"option"===t&&!!e.selected},selected:function(e){return e.parentNode&&e.parentNode.selectedIndex,e.selected===!0},empty:function(e){for(e=e.firstChild;e;e=e.nextSibling)if(e.nodeName>"@"||3===e.nodeType||4===e.nodeType)return!1;return!0},parent:function(e){return!o.pseudos.empty(e)},header:function(e){return tt.test(e.nodeName)},input:function(e){return et.test(e.nodeName)},button:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&"button"===e.type||"button"===t},text:function(e){var t;return"input"===e.nodeName.toLowerCase()&&"text"===e.type&&(null==(t=e.getAttribute("type"))||t.toLowerCase()===e.type)},first:ht(function(){return[0]}),last:ht(function(e,t){return[t-1]}),eq:ht(function(e,t,n){return[0>n?n+t:n]}),even:ht(function(e,t){var n=0;for(;t>n;n+=2)e.push(n);return e}),odd:ht(function(e,t){var n=1;for(;t>n;n+=2)e.push(n);return e}),lt:ht(function(e,t,n){var r=0>n?n+t:n;for(;--r>=0;)e.push(r);return e}),gt:ht(function(e,t,n){var r=0>n?n+t:n;for(;t>++r;)e.push(r);return e})}},o.pseudos.nth=o.pseudos.eq;for(n in{radio:!0,checkbox:!0,file:!0,password:!0,image:!0})o.pseudos[n]=ft(n);for(n in{submit:!0,reset:!0})o.pseudos[n]=dt(n);function gt(){}gt.prototype=o.filters=o.pseudos,o.setFilters=new gt;function mt(e,t){var n,r,i,a,s,l,u,c=k[e+" "];if(c)return t?0:c.slice(0);s=e,l=[],u=o.preFilter;while(s){(!n||(r=X.exec(s)))&&(r&&(s=s.slice(r[0].length)||s),l.push(i=[])),n=!1,(r=U.exec(s))&&(n=r.shift(),i.push({value:n,type:r[0].replace(z," ")}),s=s.slice(n.length));for(a in o.filter)!(r=Q[a].exec(s))||u[a]&&!(r=u[a](r))||(n=r.shift(),i.push({value:n,type:a,matches:r}),s=s.slice(n.length));if(!n)break}return t?s.length:s?at.error(e):k(e,l).slice(0)}function yt(e){var t=0,n=e.length,r="";for(;n>t;t++)r+=e[t].value;return r}function vt(e,t,n){var r=t.dir,o=n&&"parentNode"===r,a=C++;return t.first?function(t,n,i){while(t=t[r])if(1===t.nodeType||o)return e(t,n,i)}:function(t,n,s){var l,u,c,p=T+" "+a;if(s){while(t=t[r])if((1===t.nodeType||o)&&e(t,n,s))return!0}else while(t=t[r])if(1===t.nodeType||o)if(c=t[b]||(t[b]={}),(u=c[r])&&u[0]===p){if((l=u[1])===!0||l===i)return l===!0}else if(u=c[r]=[p],u[1]=e(t,n,s)||i,u[1]===!0)return!0}}function bt(e){return e.length>1?function(t,n,r){var i=e.length;while(i--)if(!e[i](t,n,r))return!1;return!0}:e[0]}function xt(e,t,n,r,i){var o,a=[],s=0,l=e.length,u=null!=t;for(;l>s;s++)(o=e[s])&&(!n||n(o,r,i))&&(a.push(o),u&&t.push(s));return a}function wt(e,t,n,r,i,o){return r&&!r[b]&&(r=wt(r)),i&&!i[b]&&(i=wt(i,o)),lt(function(o,a,s,l){var u,c,p,f=[],d=[],h=a.length,g=o||Nt(t||"*",s.nodeType?[s]:s,[]),m=!e||!o&&t?g:xt(g,f,e,s,l),y=n?i||(o?e:h||r)?[]:a:m;if(n&&n(m,y,s,l),r){u=xt(y,d),r(u,[],s,l),c=u.length;while(c--)(p=u[c])&&(y[d[c]]=!(m[d[c]]=p))}if(o){if(i||e){if(i){u=[],c=y.length;while(c--)(p=y[c])&&u.push(m[c]=p);i(null,y=[],u,l)}c=y.length;while(c--)(p=y[c])&&(u=i?F.call(o,p):f[c])>-1&&(o[u]=!(a[u]=p))}}else y=xt(y===a?y.splice(h,y.length):y),i?i(null,a,y,l):M.apply(a,y)})}function Tt(e){var t,n,r,i=e.length,a=o.relative[e[0].type],s=a||o.relative[" "],l=a?1:0,c=vt(function(e){return e===t},s,!0),p=vt(function(e){return F.call(t,e)>-1},s,!0),f=[function(e,n,r){return!a&&(r||n!==u)||((t=n).nodeType?c(e,n,r):p(e,n,r))}];for(;i>l;l++)if(n=o.relative[e[l].type])f=[vt(bt(f),n)];else{if(n=o.filter[e[l].type].apply(null,e[l].matches),n[b]){for(r=++l;i>r;r++)if(o.relative[e[r].type])break;return wt(l>1&&bt(f),l>1&&yt(e.slice(0,l-1).concat({value:" "===e[l-2].type?"*":""})).replace(z,"$1"),n,r>l&&Tt(e.slice(l,r)),i>r&&Tt(e=e.slice(r)),i>r&&yt(e))}f.push(n)}return bt(f)}function Ct(e,t){var n=0,r=t.length>0,a=e.length>0,s=function(s,l,c,p,d){var h,g,m,y=[],v=0,b="0",x=s&&[],w=null!=d,C=u,N=s||a&&o.find.TAG("*",d&&l.parentNode||l),k=T+=null==C?1:Math.random()||.1;for(w&&(u=l!==f&&l,i=n);null!=(h=N[b]);b++){if(a&&h){g=0;while(m=e[g++])if(m(h,l,c)){p.push(h);break}w&&(T=k,i=++n)}r&&((h=!m&&h)&&v--,s&&x.push(h))}if(v+=b,r&&b!==v){g=0;while(m=t[g++])m(x,y,l,c);if(s){if(v>0)while(b--)x[b]||y[b]||(y[b]=q.call(p));y=xt(y)}M.apply(p,y),w&&!s&&y.length>0&&v+t.length>1&&at.uniqueSort(p)}return w&&(T=k,u=C),x};return r?lt(s):s}l=at.compile=function(e,t){var n,r=[],i=[],o=E[e+" "];if(!o){t||(t=mt(e)),n=t.length;while(n--)o=Tt(t[n]),o[b]?r.push(o):i.push(o);o=E(e,Ct(i,r))}return o};function Nt(e,t,n){var r=0,i=t.length;for(;i>r;r++)at(e,t[r],n);return n}function kt(e,t,n,i){var a,s,u,c,p,f=mt(e);if(!i&&1===f.length){if(s=f[0]=f[0].slice(0),s.length>2&&"ID"===(u=s[0]).type&&r.getById&&9===t.nodeType&&h&&o.relative[s[1].type]){if(t=(o.find.ID(u.matches[0].replace(rt,it),t)||[])[0],!t)return n;e=e.slice(s.shift().value.length)}a=Q.needsContext.test(e)?0:s.length;while(a--){if(u=s[a],o.relative[c=u.type])break;if((p=o.find[c])&&(i=p(u.matches[0].replace(rt,it),V.test(s[0].type)&&t.parentNode||t))){if(s.splice(a,1),e=i.length&&yt(s),!e)return M.apply(n,i),n;break}}}return l(e,f)(i,t,!h,n,V.test(e)),n}r.sortStable=b.split("").sort(A).join("")===b,r.detectDuplicates=S,p(),r.sortDetached=ut(function(e){return 1&e.compareDocumentPosition(f.createElement("div"))}),ut(function(e){return e.innerHTML="<a href='#'></a>","#"===e.firstChild.getAttribute("href")})||ct("type|href|height|width",function(e,n,r){return r?t:e.getAttribute(n,"type"===n.toLowerCase()?1:2)}),r.attributes&&ut(function(e){return e.innerHTML="<input/>",e.firstChild.setAttribute("value",""),""===e.firstChild.getAttribute("value")})||ct("value",function(e,n,r){return r||"input"!==e.nodeName.toLowerCase()?t:e.defaultValue}),ut(function(e){return null==e.getAttribute("disabled")})||ct(B,function(e,n,r){var i;return r?t:(i=e.getAttributeNode(n))&&i.specified?i.value:e[n]===!0?n.toLowerCase():null}),x.find=at,x.expr=at.selectors,x.expr[":"]=x.expr.pseudos,x.unique=at.uniqueSort,x.text=at.getText,x.isXMLDoc=at.isXML,x.contains=at.contains}(e);var O={};function F(e){var t=O[e]={};return x.each(e.match(T)||[],function(e,n){t[n]=!0}),t}x.Callbacks=function(e){e="string"==typeof e?O[e]||F(e):x.extend({},e);var n,r,i,o,a,s,l=[],u=!e.once&&[],c=function(t){for(r=e.memory&&t,i=!0,a=s||0,s=0,o=l.length,n=!0;l&&o>a;a++)if(l[a].apply(t[0],t[1])===!1&&e.stopOnFalse){r=!1;break}n=!1,l&&(u?u.length&&c(u.shift()):r?l=[]:p.disable())},p={add:function(){if(l){var t=l.length;(function i(t){x.each(t,function(t,n){var r=x.type(n);"function"===r?e.unique&&p.has(n)||l.push(n):n&&n.length&&"string"!==r&&i(n)})})(arguments),n?o=l.length:r&&(s=t,c(r))}return this},remove:function(){return l&&x.each(arguments,function(e,t){var r;while((r=x.inArray(t,l,r))>-1)l.splice(r,1),n&&(o>=r&&o--,a>=r&&a--)}),this},has:function(e){return e?x.inArray(e,l)>-1:!(!l||!l.length)},empty:function(){return l=[],o=0,this},disable:function(){return l=u=r=t,this},disabled:function(){return!l},lock:function(){return u=t,r||p.disable(),this},locked:function(){return!u},fireWith:function(e,t){return!l||i&&!u||(t=t||[],t=[e,t.slice?t.slice():t],n?u.push(t):c(t)),this},fire:function(){return p.fireWith(this,arguments),this},fired:function(){return!!i}};return p},x.extend({Deferred:function(e){var t=[["resolve","done",x.Callbacks("once memory"),"resolved"],["reject","fail",x.Callbacks("once memory"),"rejected"],["notify","progress",x.Callbacks("memory")]],n="pending",r={state:function(){return n},always:function(){return i.done(arguments).fail(arguments),this},then:function(){var e=arguments;return x.Deferred(function(n){x.each(t,function(t,o){var a=o[0],s=x.isFunction(e[t])&&e[t];i[o[1]](function(){var e=s&&s.apply(this,arguments);e&&x.isFunction(e.promise)?e.promise().done(n.resolve).fail(n.reject).progress(n.notify):n[a+"With"](this===r?n.promise():this,s?[e]:arguments)})}),e=null}).promise()},promise:function(e){return null!=e?x.extend(e,r):r}},i={};return r.pipe=r.then,x.each(t,function(e,o){var a=o[2],s=o[3];r[o[1]]=a.add,s&&a.add(function(){n=s},t[1^e][2].disable,t[2][2].lock),i[o[0]]=function(){return i[o[0]+"With"](this===i?r:this,arguments),this},i[o[0]+"With"]=a.fireWith}),r.promise(i),e&&e.call(i,i),i},when:function(e){var t=0,n=g.call(arguments),r=n.length,i=1!==r||e&&x.isFunction(e.promise)?r:0,o=1===i?e:x.Deferred(),a=function(e,t,n){return function(r){t[e]=this,n[e]=arguments.length>1?g.call(arguments):r,n===s?o.notifyWith(t,n):--i||o.resolveWith(t,n)}},s,l,u;if(r>1)for(s=Array(r),l=Array(r),u=Array(r);r>t;t++)n[t]&&x.isFunction(n[t].promise)?n[t].promise().done(a(t,u,n)).fail(o.reject).progress(a(t,l,s)):--i;return i||o.resolveWith(u,n),o.promise()}}),x.support=function(t){var n,r,o,s,l,u,c,p,f,d=a.createElement("div");if(d.setAttribute("className","t"),d.innerHTML="  <link/><table></table><a href='/a'>a</a><input type='checkbox'/>",n=d.getElementsByTagName("*")||[],r=d.getElementsByTagName("a")[0],!r||!r.style||!n.length)return t;s=a.createElement("select"),u=s.appendChild(a.createElement("option")),o=d.getElementsByTagName("input")[0],r.style.cssText="top:1px;float:left;opacity:.5",t.getSetAttribute="t"!==d.className,t.leadingWhitespace=3===d.firstChild.nodeType,t.tbody=!d.getElementsByTagName("tbody").length,t.htmlSerialize=!!d.getElementsByTagName("link").length,t.style=/top/.test(r.getAttribute("style")),t.hrefNormalized="/a"===r.getAttribute("href"),t.opacity=/^0.5/.test(r.style.opacity),t.cssFloat=!!r.style.cssFloat,t.checkOn=!!o.value,t.optSelected=u.selected,t.enctype=!!a.createElement("form").enctype,t.html5Clone="<:nav></:nav>"!==a.createElement("nav").cloneNode(!0).outerHTML,t.inlineBlockNeedsLayout=!1,t.shrinkWrapBlocks=!1,t.pixelPosition=!1,t.deleteExpando=!0,t.noCloneEvent=!0,t.reliableMarginRight=!0,t.boxSizingReliable=!0,o.checked=!0,t.noCloneChecked=o.cloneNode(!0).checked,s.disabled=!0,t.optDisabled=!u.disabled;try{delete d.test}catch(h){t.deleteExpando=!1}o=a.createElement("input"),o.setAttribute("value",""),t.input=""===o.getAttribute("value"),o.value="t",o.setAttribute("type","radio"),t.radioValue="t"===o.value,o.setAttribute("checked","t"),o.setAttribute("name","t"),l=a.createDocumentFragment(),l.appendChild(o),t.appendChecked=o.checked,t.checkClone=l.cloneNode(!0).cloneNode(!0).lastChild.checked,d.attachEvent&&(d.attachEvent("onclick",function(){t.noCloneEvent=!1}),d.cloneNode(!0).click());for(f in{submit:!0,change:!0,focusin:!0})d.setAttribute(c="on"+f,"t"),t[f+"Bubbles"]=c in e||d.attributes[c].expando===!1;d.style.backgroundClip="content-box",d.cloneNode(!0).style.backgroundClip="",t.clearCloneStyle="content-box"===d.style.backgroundClip;for(f in x(t))break;return t.ownLast="0"!==f,x(function(){var n,r,o,s="padding:0;margin:0;border:0;display:block;box-sizing:content-box;-moz-box-sizing:content-box;-webkit-box-sizing:content-box;",l=a.getElementsByTagName("body")[0];l&&(n=a.createElement("div"),n.style.cssText="border:0;width:0;height:0;position:absolute;top:0;left:-9999px;margin-top:1px",l.appendChild(n).appendChild(d),d.innerHTML="<table><tr><td></td><td>t</td></tr></table>",o=d.getElementsByTagName("td"),o[0].style.cssText="padding:0;margin:0;border:0;display:none",p=0===o[0].offsetHeight,o[0].style.display="",o[1].style.display="none",t.reliableHiddenOffsets=p&&0===o[0].offsetHeight,d.innerHTML="",d.style.cssText="box-sizing:border-box;-moz-box-sizing:border-box;-webkit-box-sizing:border-box;padding:1px;border:1px;display:block;width:4px;margin-top:1%;position:absolute;top:1%;",x.swap(l,null!=l.style.zoom?{zoom:1}:{},function(){t.boxSizing=4===d.offsetWidth}),e.getComputedStyle&&(t.pixelPosition="1%"!==(e.getComputedStyle(d,null)||{}).top,t.boxSizingReliable="4px"===(e.getComputedStyle(d,null)||{width:"4px"}).width,r=d.appendChild(a.createElement("div")),r.style.cssText=d.style.cssText=s,r.style.marginRight=r.style.width="0",d.style.width="1px",t.reliableMarginRight=!parseFloat((e.getComputedStyle(r,null)||{}).marginRight)),typeof d.style.zoom!==i&&(d.innerHTML="",d.style.cssText=s+"width:1px;padding:1px;display:inline;zoom:1",t.inlineBlockNeedsLayout=3===d.offsetWidth,d.style.display="block",d.innerHTML="<div></div>",d.firstChild.style.width="5px",t.shrinkWrapBlocks=3!==d.offsetWidth,t.inlineBlockNeedsLayout&&(l.style.zoom=1)),l.removeChild(n),n=d=o=r=null)}),n=s=l=u=r=o=null,t
	}({});var B=/(?:\{[\s\S]*\}|\[[\s\S]*\])$/,P=/([A-Z])/g;function R(e,n,r,i){if(x.acceptData(e)){var o,a,s=x.expando,l=e.nodeType,u=l?x.cache:e,c=l?e[s]:e[s]&&s;if(c&&u[c]&&(i||u[c].data)||r!==t||"string"!=typeof n)return c||(c=l?e[s]=p.pop()||x.guid++:s),u[c]||(u[c]=l?{}:{toJSON:x.noop}),("object"==typeof n||"function"==typeof n)&&(i?u[c]=x.extend(u[c],n):u[c].data=x.extend(u[c].data,n)),a=u[c],i||(a.data||(a.data={}),a=a.data),r!==t&&(a[x.camelCase(n)]=r),"string"==typeof n?(o=a[n],null==o&&(o=a[x.camelCase(n)])):o=a,o}}function W(e,t,n){if(x.acceptData(e)){var r,i,o=e.nodeType,a=o?x.cache:e,s=o?e[x.expando]:x.expando;if(a[s]){if(t&&(r=n?a[s]:a[s].data)){x.isArray(t)?t=t.concat(x.map(t,x.camelCase)):t in r?t=[t]:(t=x.camelCase(t),t=t in r?[t]:t.split(" ")),i=t.length;while(i--)delete r[t[i]];if(n?!I(r):!x.isEmptyObject(r))return}(n||(delete a[s].data,I(a[s])))&&(o?x.cleanData([e],!0):x.support.deleteExpando||a!=a.window?delete a[s]:a[s]=null)}}}x.extend({cache:{},noData:{applet:!0,embed:!0,object:"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"},hasData:function(e){return e=e.nodeType?x.cache[e[x.expando]]:e[x.expando],!!e&&!I(e)},data:function(e,t,n){return R(e,t,n)},removeData:function(e,t){return W(e,t)},_data:function(e,t,n){return R(e,t,n,!0)},_removeData:function(e,t){return W(e,t,!0)},acceptData:function(e){if(e.nodeType&&1!==e.nodeType&&9!==e.nodeType)return!1;var t=e.nodeName&&x.noData[e.nodeName.toLowerCase()];return!t||t!==!0&&e.getAttribute("classid")===t}}),x.fn.extend({data:function(e,n){var r,i,o=null,a=0,s=this[0];if(e===t){if(this.length&&(o=x.data(s),1===s.nodeType&&!x._data(s,"parsedAttrs"))){for(r=s.attributes;r.length>a;a++)i=r[a].name,0===i.indexOf("data-")&&(i=x.camelCase(i.slice(5)),$(s,i,o[i]));x._data(s,"parsedAttrs",!0)}return o}return"object"==typeof e?this.each(function(){x.data(this,e)}):arguments.length>1?this.each(function(){x.data(this,e,n)}):s?$(s,e,x.data(s,e)):null},removeData:function(e){return this.each(function(){x.removeData(this,e)})}});function $(e,n,r){if(r===t&&1===e.nodeType){var i="data-"+n.replace(P,"-$1").toLowerCase();if(r=e.getAttribute(i),"string"==typeof r){try{r="true"===r?!0:"false"===r?!1:"null"===r?null:+r+""===r?+r:B.test(r)?x.parseJSON(r):r}catch(o){}x.data(e,n,r)}else r=t}return r}function I(e){var t;for(t in e)if(("data"!==t||!x.isEmptyObject(e[t]))&&"toJSON"!==t)return!1;return!0}x.extend({queue:function(e,n,r){var i;return e?(n=(n||"fx")+"queue",i=x._data(e,n),r&&(!i||x.isArray(r)?i=x._data(e,n,x.makeArray(r)):i.push(r)),i||[]):t},dequeue:function(e,t){t=t||"fx";var n=x.queue(e,t),r=n.length,i=n.shift(),o=x._queueHooks(e,t),a=function(){x.dequeue(e,t)};"inprogress"===i&&(i=n.shift(),r--),i&&("fx"===t&&n.unshift("inprogress"),delete o.stop,i.call(e,a,o)),!r&&o&&o.empty.fire()},_queueHooks:function(e,t){var n=t+"queueHooks";return x._data(e,n)||x._data(e,n,{empty:x.Callbacks("once memory").add(function(){x._removeData(e,t+"queue"),x._removeData(e,n)})})}}),x.fn.extend({queue:function(e,n){var r=2;return"string"!=typeof e&&(n=e,e="fx",r--),r>arguments.length?x.queue(this[0],e):n===t?this:this.each(function(){var t=x.queue(this,e,n);x._queueHooks(this,e),"fx"===e&&"inprogress"!==t[0]&&x.dequeue(this,e)})},dequeue:function(e){return this.each(function(){x.dequeue(this,e)})},delay:function(e,t){return e=x.fx?x.fx.speeds[e]||e:e,t=t||"fx",this.queue(t,function(t,n){var r=setTimeout(t,e);n.stop=function(){clearTimeout(r)}})},clearQueue:function(e){return this.queue(e||"fx",[])},promise:function(e,n){var r,i=1,o=x.Deferred(),a=this,s=this.length,l=function(){--i||o.resolveWith(a,[a])};"string"!=typeof e&&(n=e,e=t),e=e||"fx";while(s--)r=x._data(a[s],e+"queueHooks"),r&&r.empty&&(i++,r.empty.add(l));return l(),o.promise(n)}});var z,X,U=/[\t\r\n\f]/g,V=/\r/g,Y=/^(?:input|select|textarea|button|object)$/i,J=/^(?:a|area)$/i,G=/^(?:checked|selected)$/i,Q=x.support.getSetAttribute,K=x.support.input;x.fn.extend({attr:function(e,t){return x.access(this,x.attr,e,t,arguments.length>1)},removeAttr:function(e){return this.each(function(){x.removeAttr(this,e)})},prop:function(e,t){return x.access(this,x.prop,e,t,arguments.length>1)},removeProp:function(e){return e=x.propFix[e]||e,this.each(function(){try{this[e]=t,delete this[e]}catch(n){}})},addClass:function(e){var t,n,r,i,o,a=0,s=this.length,l="string"==typeof e&&e;if(x.isFunction(e))return this.each(function(t){x(this).addClass(e.call(this,t,this.className))});if(l)for(t=(e||"").match(T)||[];s>a;a++)if(n=this[a],r=1===n.nodeType&&(n.className?(" "+n.className+" ").replace(U," "):" ")){o=0;while(i=t[o++])0>r.indexOf(" "+i+" ")&&(r+=i+" ");n.className=x.trim(r)}return this},removeClass:function(e){var t,n,r,i,o,a=0,s=this.length,l=0===arguments.length||"string"==typeof e&&e;if(x.isFunction(e))return this.each(function(t){x(this).removeClass(e.call(this,t,this.className))});if(l)for(t=(e||"").match(T)||[];s>a;a++)if(n=this[a],r=1===n.nodeType&&(n.className?(" "+n.className+" ").replace(U," "):"")){o=0;while(i=t[o++])while(r.indexOf(" "+i+" ")>=0)r=r.replace(" "+i+" "," ");n.className=e?x.trim(r):""}return this},toggleClass:function(e,t){var n=typeof e;return"boolean"==typeof t&&"string"===n?t?this.addClass(e):this.removeClass(e):x.isFunction(e)?this.each(function(n){x(this).toggleClass(e.call(this,n,this.className,t),t)}):this.each(function(){if("string"===n){var t,r=0,o=x(this),a=e.match(T)||[];while(t=a[r++])o.hasClass(t)?o.removeClass(t):o.addClass(t)}else(n===i||"boolean"===n)&&(this.className&&x._data(this,"__className__",this.className),this.className=this.className||e===!1?"":x._data(this,"__className__")||"")})},hasClass:function(e){var t=" "+e+" ",n=0,r=this.length;for(;r>n;n++)if(1===this[n].nodeType&&(" "+this[n].className+" ").replace(U," ").indexOf(t)>=0)return!0;return!1},val:function(e){var n,r,i,o=this[0];{if(arguments.length)return i=x.isFunction(e),this.each(function(n){var o;1===this.nodeType&&(o=i?e.call(this,n,x(this).val()):e,null==o?o="":"number"==typeof o?o+="":x.isArray(o)&&(o=x.map(o,function(e){return null==e?"":e+""})),r=x.valHooks[this.type]||x.valHooks[this.nodeName.toLowerCase()],r&&"set"in r&&r.set(this,o,"value")!==t||(this.value=o))});if(o)return r=x.valHooks[o.type]||x.valHooks[o.nodeName.toLowerCase()],r&&"get"in r&&(n=r.get(o,"value"))!==t?n:(n=o.value,"string"==typeof n?n.replace(V,""):null==n?"":n)}}}),x.extend({valHooks:{option:{get:function(e){var t=x.find.attr(e,"value");return null!=t?t:e.text}},select:{get:function(e){var t,n,r=e.options,i=e.selectedIndex,o="select-one"===e.type||0>i,a=o?null:[],s=o?i+1:r.length,l=0>i?s:o?i:0;for(;s>l;l++)if(n=r[l],!(!n.selected&&l!==i||(x.support.optDisabled?n.disabled:null!==n.getAttribute("disabled"))||n.parentNode.disabled&&x.nodeName(n.parentNode,"optgroup"))){if(t=x(n).val(),o)return t;a.push(t)}return a},set:function(e,t){var n,r,i=e.options,o=x.makeArray(t),a=i.length;while(a--)r=i[a],(r.selected=x.inArray(x(r).val(),o)>=0)&&(n=!0);return n||(e.selectedIndex=-1),o}}},attr:function(e,n,r){var o,a,s=e.nodeType;if(e&&3!==s&&8!==s&&2!==s)return typeof e.getAttribute===i?x.prop(e,n,r):(1===s&&x.isXMLDoc(e)||(n=n.toLowerCase(),o=x.attrHooks[n]||(x.expr.match.bool.test(n)?X:z)),r===t?o&&"get"in o&&null!==(a=o.get(e,n))?a:(a=x.find.attr(e,n),null==a?t:a):null!==r?o&&"set"in o&&(a=o.set(e,r,n))!==t?a:(e.setAttribute(n,r+""),r):(x.removeAttr(e,n),t))},removeAttr:function(e,t){var n,r,i=0,o=t&&t.match(T);if(o&&1===e.nodeType)while(n=o[i++])r=x.propFix[n]||n,x.expr.match.bool.test(n)?K&&Q||!G.test(n)?e[r]=!1:e[x.camelCase("default-"+n)]=e[r]=!1:x.attr(e,n,""),e.removeAttribute(Q?n:r)},attrHooks:{type:{set:function(e,t){if(!x.support.radioValue&&"radio"===t&&x.nodeName(e,"input")){var n=e.value;return e.setAttribute("type",t),n&&(e.value=n),t}}}},propFix:{"for":"htmlFor","class":"className"},prop:function(e,n,r){var i,o,a,s=e.nodeType;if(e&&3!==s&&8!==s&&2!==s)return a=1!==s||!x.isXMLDoc(e),a&&(n=x.propFix[n]||n,o=x.propHooks[n]),r!==t?o&&"set"in o&&(i=o.set(e,r,n))!==t?i:e[n]=r:o&&"get"in o&&null!==(i=o.get(e,n))?i:e[n]},propHooks:{tabIndex:{get:function(e){var t=x.find.attr(e,"tabindex");return t?parseInt(t,10):Y.test(e.nodeName)||J.test(e.nodeName)&&e.href?0:-1}}}}),X={set:function(e,t,n){return t===!1?x.removeAttr(e,n):K&&Q||!G.test(n)?e.setAttribute(!Q&&x.propFix[n]||n,n):e[x.camelCase("default-"+n)]=e[n]=!0,n}},x.each(x.expr.match.bool.source.match(/\w+/g),function(e,n){var r=x.expr.attrHandle[n]||x.find.attr;x.expr.attrHandle[n]=K&&Q||!G.test(n)?function(e,n,i){var o=x.expr.attrHandle[n],a=i?t:(x.expr.attrHandle[n]=t)!=r(e,n,i)?n.toLowerCase():null;return x.expr.attrHandle[n]=o,a}:function(e,n,r){return r?t:e[x.camelCase("default-"+n)]?n.toLowerCase():null}}),K&&Q||(x.attrHooks.value={set:function(e,n,r){return x.nodeName(e,"input")?(e.defaultValue=n,t):z&&z.set(e,n,r)}}),Q||(z={set:function(e,n,r){var i=e.getAttributeNode(r);return i||e.setAttributeNode(i=e.ownerDocument.createAttribute(r)),i.value=n+="","value"===r||n===e.getAttribute(r)?n:t}},x.expr.attrHandle.id=x.expr.attrHandle.name=x.expr.attrHandle.coords=function(e,n,r){var i;return r?t:(i=e.getAttributeNode(n))&&""!==i.value?i.value:null},x.valHooks.button={get:function(e,n){var r=e.getAttributeNode(n);return r&&r.specified?r.value:t},set:z.set},x.attrHooks.contenteditable={set:function(e,t,n){z.set(e,""===t?!1:t,n)}},x.each(["width","height"],function(e,n){x.attrHooks[n]={set:function(e,r){return""===r?(e.setAttribute(n,"auto"),r):t}}})),x.support.hrefNormalized||x.each(["href","src"],function(e,t){x.propHooks[t]={get:function(e){return e.getAttribute(t,4)}}}),x.support.style||(x.attrHooks.style={get:function(e){return e.style.cssText||t},set:function(e,t){return e.style.cssText=t+""}}),x.support.optSelected||(x.propHooks.selected={get:function(e){var t=e.parentNode;return t&&(t.selectedIndex,t.parentNode&&t.parentNode.selectedIndex),null}}),x.each(["tabIndex","readOnly","maxLength","cellSpacing","cellPadding","rowSpan","colSpan","useMap","frameBorder","contentEditable"],function(){x.propFix[this.toLowerCase()]=this}),x.support.enctype||(x.propFix.enctype="encoding"),x.each(["radio","checkbox"],function(){x.valHooks[this]={set:function(e,n){return x.isArray(n)?e.checked=x.inArray(x(e).val(),n)>=0:t}},x.support.checkOn||(x.valHooks[this].get=function(e){return null===e.getAttribute("value")?"on":e.value})});var Z=/^(?:input|select|textarea)$/i,et=/^key/,tt=/^(?:mouse|contextmenu)|click/,nt=/^(?:focusinfocus|focusoutblur)$/,rt=/^([^.]*)(?:\.(.+)|)$/;function it(){return!0}function ot(){return!1}function at(){try{return a.activeElement}catch(e){}}x.event={global:{},add:function(e,n,r,o,a){var s,l,u,c,p,f,d,h,g,m,y,v=x._data(e);if(v){r.handler&&(c=r,r=c.handler,a=c.selector),r.guid||(r.guid=x.guid++),(l=v.events)||(l=v.events={}),(f=v.handle)||(f=v.handle=function(e){return typeof x===i||e&&x.event.triggered===e.type?t:x.event.dispatch.apply(f.elem,arguments)},f.elem=e),n=(n||"").match(T)||[""],u=n.length;while(u--)s=rt.exec(n[u])||[],g=y=s[1],m=(s[2]||"").split(".").sort(),g&&(p=x.event.special[g]||{},g=(a?p.delegateType:p.bindType)||g,p=x.event.special[g]||{},d=x.extend({type:g,origType:y,data:o,handler:r,guid:r.guid,selector:a,needsContext:a&&x.expr.match.needsContext.test(a),namespace:m.join(".")},c),(h=l[g])||(h=l[g]=[],h.delegateCount=0,p.setup&&p.setup.call(e,o,m,f)!==!1||(e.addEventListener?e.addEventListener(g,f,!1):e.attachEvent&&e.attachEvent("on"+g,f))),p.add&&(p.add.call(e,d),d.handler.guid||(d.handler.guid=r.guid)),a?h.splice(h.delegateCount++,0,d):h.push(d),x.event.global[g]=!0);e=null}},remove:function(e,t,n,r,i){var o,a,s,l,u,c,p,f,d,h,g,m=x.hasData(e)&&x._data(e);if(m&&(c=m.events)){t=(t||"").match(T)||[""],u=t.length;while(u--)if(s=rt.exec(t[u])||[],d=g=s[1],h=(s[2]||"").split(".").sort(),d){p=x.event.special[d]||{},d=(r?p.delegateType:p.bindType)||d,f=c[d]||[],s=s[2]&&RegExp("(^|\\.)"+h.join("\\.(?:.*\\.|)")+"(\\.|$)"),l=o=f.length;while(o--)a=f[o],!i&&g!==a.origType||n&&n.guid!==a.guid||s&&!s.test(a.namespace)||r&&r!==a.selector&&("**"!==r||!a.selector)||(f.splice(o,1),a.selector&&f.delegateCount--,p.remove&&p.remove.call(e,a));l&&!f.length&&(p.teardown&&p.teardown.call(e,h,m.handle)!==!1||x.removeEvent(e,d,m.handle),delete c[d])}else for(d in c)x.event.remove(e,d+t[u],n,r,!0);x.isEmptyObject(c)&&(delete m.handle,x._removeData(e,"events"))}},trigger:function(n,r,i,o){var s,l,u,c,p,f,d,h=[i||a],g=v.call(n,"type")?n.type:n,m=v.call(n,"namespace")?n.namespace.split("."):[];if(u=f=i=i||a,3!==i.nodeType&&8!==i.nodeType&&!nt.test(g+x.event.triggered)&&(g.indexOf(".")>=0&&(m=g.split("."),g=m.shift(),m.sort()),l=0>g.indexOf(":")&&"on"+g,n=n[x.expando]?n:new x.Event(g,"object"==typeof n&&n),n.isTrigger=o?2:3,n.namespace=m.join("."),n.namespace_re=n.namespace?RegExp("(^|\\.)"+m.join("\\.(?:.*\\.|)")+"(\\.|$)"):null,n.result=t,n.target||(n.target=i),r=null==r?[n]:x.makeArray(r,[n]),p=x.event.special[g]||{},o||!p.trigger||p.trigger.apply(i,r)!==!1)){if(!o&&!p.noBubble&&!x.isWindow(i)){for(c=p.delegateType||g,nt.test(c+g)||(u=u.parentNode);u;u=u.parentNode)h.push(u),f=u;f===(i.ownerDocument||a)&&h.push(f.defaultView||f.parentWindow||e)}d=0;while((u=h[d++])&&!n.isPropagationStopped())n.type=d>1?c:p.bindType||g,s=(x._data(u,"events")||{})[n.type]&&x._data(u,"handle"),s&&s.apply(u,r),s=l&&u[l],s&&x.acceptData(u)&&s.apply&&s.apply(u,r)===!1&&n.preventDefault();if(n.type=g,!o&&!n.isDefaultPrevented()&&(!p._default||p._default.apply(h.pop(),r)===!1)&&x.acceptData(i)&&l&&i[g]&&!x.isWindow(i)){f=i[l],f&&(i[l]=null),x.event.triggered=g;try{i[g]()}catch(y){}x.event.triggered=t,f&&(i[l]=f)}return n.result}},dispatch:function(e){e=x.event.fix(e);var n,r,i,o,a,s=[],l=g.call(arguments),u=(x._data(this,"events")||{})[e.type]||[],c=x.event.special[e.type]||{};if(l[0]=e,e.delegateTarget=this,!c.preDispatch||c.preDispatch.call(this,e)!==!1){s=x.event.handlers.call(this,e,u),n=0;while((o=s[n++])&&!e.isPropagationStopped()){e.currentTarget=o.elem,a=0;while((i=o.handlers[a++])&&!e.isImmediatePropagationStopped())(!e.namespace_re||e.namespace_re.test(i.namespace))&&(e.handleObj=i,e.data=i.data,r=((x.event.special[i.origType]||{}).handle||i.handler).apply(o.elem,l),r!==t&&(e.result=r)===!1&&(e.preventDefault(),e.stopPropagation()))}return c.postDispatch&&c.postDispatch.call(this,e),e.result}},handlers:function(e,n){var r,i,o,a,s=[],l=n.delegateCount,u=e.target;if(l&&u.nodeType&&(!e.button||"click"!==e.type))for(;u!=this;u=u.parentNode||this)if(1===u.nodeType&&(u.disabled!==!0||"click"!==e.type)){for(o=[],a=0;l>a;a++)i=n[a],r=i.selector+" ",o[r]===t&&(o[r]=i.needsContext?x(r,this).index(u)>=0:x.find(r,this,null,[u]).length),o[r]&&o.push(i);o.length&&s.push({elem:u,handlers:o})}return n.length>l&&s.push({elem:this,handlers:n.slice(l)}),s},fix:function(e){if(e[x.expando])return e;var t,n,r,i=e.type,o=e,s=this.fixHooks[i];s||(this.fixHooks[i]=s=tt.test(i)?this.mouseHooks:et.test(i)?this.keyHooks:{}),r=s.props?this.props.concat(s.props):this.props,e=new x.Event(o),t=r.length;while(t--)n=r[t],e[n]=o[n];return e.target||(e.target=o.srcElement||a),3===e.target.nodeType&&(e.target=e.target.parentNode),e.metaKey=!!e.metaKey,s.filter?s.filter(e,o):e},props:"altKey bubbles cancelable ctrlKey currentTarget eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),fixHooks:{},keyHooks:{props:"char charCode key keyCode".split(" "),filter:function(e,t){return null==e.which&&(e.which=null!=t.charCode?t.charCode:t.keyCode),e}},mouseHooks:{props:"button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),filter:function(e,n){var r,i,o,s=n.button,l=n.fromElement;return null==e.pageX&&null!=n.clientX&&(i=e.target.ownerDocument||a,o=i.documentElement,r=i.body,e.pageX=n.clientX+(o&&o.scrollLeft||r&&r.scrollLeft||0)-(o&&o.clientLeft||r&&r.clientLeft||0),e.pageY=n.clientY+(o&&o.scrollTop||r&&r.scrollTop||0)-(o&&o.clientTop||r&&r.clientTop||0)),!e.relatedTarget&&l&&(e.relatedTarget=l===e.target?n.toElement:l),e.which||s===t||(e.which=1&s?1:2&s?3:4&s?2:0),e}},special:{load:{noBubble:!0},focus:{trigger:function(){if(this!==at()&&this.focus)try{return this.focus(),!1}catch(e){}},delegateType:"focusin"},blur:{trigger:function(){return this===at()&&this.blur?(this.blur(),!1):t},delegateType:"focusout"},click:{trigger:function(){return x.nodeName(this,"input")&&"checkbox"===this.type&&this.click?(this.click(),!1):t},_default:function(e){return x.nodeName(e.target,"a")}},beforeunload:{postDispatch:function(e){e.result!==t&&(e.originalEvent.returnValue=e.result)}}},simulate:function(e,t,n,r){var i=x.extend(new x.Event,n,{type:e,isSimulated:!0,originalEvent:{}});r?x.event.trigger(i,null,t):x.event.dispatch.call(t,i),i.isDefaultPrevented()&&n.preventDefault()}},x.removeEvent=a.removeEventListener?function(e,t,n){e.removeEventListener&&e.removeEventListener(t,n,!1)}:function(e,t,n){var r="on"+t;e.detachEvent&&(typeof e[r]===i&&(e[r]=null),e.detachEvent(r,n))},x.Event=function(e,n){return this instanceof x.Event?(e&&e.type?(this.originalEvent=e,this.type=e.type,this.isDefaultPrevented=e.defaultPrevented||e.returnValue===!1||e.getPreventDefault&&e.getPreventDefault()?it:ot):this.type=e,n&&x.extend(this,n),this.timeStamp=e&&e.timeStamp||x.now(),this[x.expando]=!0,t):new x.Event(e,n)},x.Event.prototype={isDefaultPrevented:ot,isPropagationStopped:ot,isImmediatePropagationStopped:ot,preventDefault:function(){var e=this.originalEvent;this.isDefaultPrevented=it,e&&(e.preventDefault?e.preventDefault():e.returnValue=!1)},stopPropagation:function(){var e=this.originalEvent;this.isPropagationStopped=it,e&&(e.stopPropagation&&e.stopPropagation(),e.cancelBubble=!0)},stopImmediatePropagation:function(){this.isImmediatePropagationStopped=it,this.stopPropagation()}},x.each({mouseenter:"mouseover",mouseleave:"mouseout"},function(e,t){x.event.special[e]={delegateType:t,bindType:t,handle:function(e){var n,r=this,i=e.relatedTarget,o=e.handleObj;return(!i||i!==r&&!x.contains(r,i))&&(e.type=o.origType,n=o.handler.apply(this,arguments),e.type=t),n}}}),x.support.submitBubbles||(x.event.special.submit={setup:function(){return x.nodeName(this,"form")?!1:(x.event.add(this,"click._submit keypress._submit",function(e){var n=e.target,r=x.nodeName(n,"input")||x.nodeName(n,"button")?n.form:t;r&&!x._data(r,"submitBubbles")&&(x.event.add(r,"submit._submit",function(e){e._submit_bubble=!0}),x._data(r,"submitBubbles",!0))}),t)},postDispatch:function(e){e._submit_bubble&&(delete e._submit_bubble,this.parentNode&&!e.isTrigger&&x.event.simulate("submit",this.parentNode,e,!0))},teardown:function(){return x.nodeName(this,"form")?!1:(x.event.remove(this,"._submit"),t)}}),x.support.changeBubbles||(x.event.special.change={setup:function(){return Z.test(this.nodeName)?(("checkbox"===this.type||"radio"===this.type)&&(x.event.add(this,"propertychange._change",function(e){"checked"===e.originalEvent.propertyName&&(this._just_changed=!0)}),x.event.add(this,"click._change",function(e){this._just_changed&&!e.isTrigger&&(this._just_changed=!1),x.event.simulate("change",this,e,!0)})),!1):(x.event.add(this,"beforeactivate._change",function(e){var t=e.target;Z.test(t.nodeName)&&!x._data(t,"changeBubbles")&&(x.event.add(t,"change._change",function(e){!this.parentNode||e.isSimulated||e.isTrigger||x.event.simulate("change",this.parentNode,e,!0)}),x._data(t,"changeBubbles",!0))}),t)},handle:function(e){var n=e.target;return this!==n||e.isSimulated||e.isTrigger||"radio"!==n.type&&"checkbox"!==n.type?e.handleObj.handler.apply(this,arguments):t},teardown:function(){return x.event.remove(this,"._change"),!Z.test(this.nodeName)}}),x.support.focusinBubbles||x.each({focus:"focusin",blur:"focusout"},function(e,t){var n=0,r=function(e){x.event.simulate(t,e.target,x.event.fix(e),!0)};x.event.special[t]={setup:function(){0===n++&&a.addEventListener(e,r,!0)},teardown:function(){0===--n&&a.removeEventListener(e,r,!0)}}}),x.fn.extend({on:function(e,n,r,i,o){var a,s;if("object"==typeof e){"string"!=typeof n&&(r=r||n,n=t);for(a in e)this.on(a,n,r,e[a],o);return this}if(null==r&&null==i?(i=n,r=n=t):null==i&&("string"==typeof n?(i=r,r=t):(i=r,r=n,n=t)),i===!1)i=ot;else if(!i)return this;return 1===o&&(s=i,i=function(e){return x().off(e),s.apply(this,arguments)},i.guid=s.guid||(s.guid=x.guid++)),this.each(function(){x.event.add(this,e,i,r,n)})},one:function(e,t,n,r){return this.on(e,t,n,r,1)},off:function(e,n,r){var i,o;if(e&&e.preventDefault&&e.handleObj)return i=e.handleObj,x(e.delegateTarget).off(i.namespace?i.origType+"."+i.namespace:i.origType,i.selector,i.handler),this;if("object"==typeof e){for(o in e)this.off(o,n,e[o]);return this}return(n===!1||"function"==typeof n)&&(r=n,n=t),r===!1&&(r=ot),this.each(function(){x.event.remove(this,e,r,n)})},trigger:function(e,t){return this.each(function(){x.event.trigger(e,t,this)})},triggerHandler:function(e,n){var r=this[0];return r?x.event.trigger(e,n,r,!0):t}});var st=/^.[^:#\[\.,]*$/,lt=/^(?:parents|prev(?:Until|All))/,ut=x.expr.match.needsContext,ct={children:!0,contents:!0,next:!0,prev:!0};x.fn.extend({find:function(e){var t,n=[],r=this,i=r.length;if("string"!=typeof e)return this.pushStack(x(e).filter(function(){for(t=0;i>t;t++)if(x.contains(r[t],this))return!0}));for(t=0;i>t;t++)x.find(e,r[t],n);return n=this.pushStack(i>1?x.unique(n):n),n.selector=this.selector?this.selector+" "+e:e,n},has:function(e){var t,n=x(e,this),r=n.length;return this.filter(function(){for(t=0;r>t;t++)if(x.contains(this,n[t]))return!0})},not:function(e){return this.pushStack(ft(this,e||[],!0))},filter:function(e){return this.pushStack(ft(this,e||[],!1))},is:function(e){return!!ft(this,"string"==typeof e&&ut.test(e)?x(e):e||[],!1).length},closest:function(e,t){var n,r=0,i=this.length,o=[],a=ut.test(e)||"string"!=typeof e?x(e,t||this.context):0;for(;i>r;r++)for(n=this[r];n&&n!==t;n=n.parentNode)if(11>n.nodeType&&(a?a.index(n)>-1:1===n.nodeType&&x.find.matchesSelector(n,e))){n=o.push(n);break}return this.pushStack(o.length>1?x.unique(o):o)},index:function(e){return e?"string"==typeof e?x.inArray(this[0],x(e)):x.inArray(e.jquery?e[0]:e,this):this[0]&&this[0].parentNode?this.first().prevAll().length:-1},add:function(e,t){var n="string"==typeof e?x(e,t):x.makeArray(e&&e.nodeType?[e]:e),r=x.merge(this.get(),n);return this.pushStack(x.unique(r))},addBack:function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}});function pt(e,t){do e=e[t];while(e&&1!==e.nodeType);return e}x.each({parent:function(e){var t=e.parentNode;return t&&11!==t.nodeType?t:null},parents:function(e){return x.dir(e,"parentNode")},parentsUntil:function(e,t,n){return x.dir(e,"parentNode",n)},next:function(e){return pt(e,"nextSibling")},prev:function(e){return pt(e,"previousSibling")},nextAll:function(e){return x.dir(e,"nextSibling")},prevAll:function(e){return x.dir(e,"previousSibling")},nextUntil:function(e,t,n){return x.dir(e,"nextSibling",n)},prevUntil:function(e,t,n){return x.dir(e,"previousSibling",n)},siblings:function(e){return x.sibling((e.parentNode||{}).firstChild,e)},children:function(e){return x.sibling(e.firstChild)},contents:function(e){return x.nodeName(e,"iframe")?e.contentDocument||e.contentWindow.document:x.merge([],e.childNodes)}},function(e,t){x.fn[e]=function(n,r){var i=x.map(this,t,n);return"Until"!==e.slice(-5)&&(r=n),r&&"string"==typeof r&&(i=x.filter(r,i)),this.length>1&&(ct[e]||(i=x.unique(i)),lt.test(e)&&(i=i.reverse())),this.pushStack(i)}}),x.extend({filter:function(e,t,n){var r=t[0];return n&&(e=":not("+e+")"),1===t.length&&1===r.nodeType?x.find.matchesSelector(r,e)?[r]:[]:x.find.matches(e,x.grep(t,function(e){return 1===e.nodeType}))},dir:function(e,n,r){var i=[],o=e[n];while(o&&9!==o.nodeType&&(r===t||1!==o.nodeType||!x(o).is(r)))1===o.nodeType&&i.push(o),o=o[n];return i},sibling:function(e,t){var n=[];for(;e;e=e.nextSibling)1===e.nodeType&&e!==t&&n.push(e);return n}});function ft(e,t,n){if(x.isFunction(t))return x.grep(e,function(e,r){return!!t.call(e,r,e)!==n});if(t.nodeType)return x.grep(e,function(e){return e===t!==n});if("string"==typeof t){if(st.test(t))return x.filter(t,e,n);t=x.filter(t,e)}return x.grep(e,function(e){return x.inArray(e,t)>=0!==n})}function dt(e){var t=ht.split("|"),n=e.createDocumentFragment();if(n.createElement)while(t.length)n.createElement(t.pop());return n}var ht="abbr|article|aside|audio|bdi|canvas|data|datalist|details|figcaption|figure|footer|header|hgroup|mark|meter|nav|output|progress|section|summary|time|video",gt=/ jQuery\d+="(?:null|\d+)"/g,mt=RegExp("<(?:"+ht+")[\\s/>]","i"),yt=/^\s+/,vt=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi,bt=/<([\w:]+)/,xt=/<tbody/i,wt=/<|&#?\w+;/,Tt=/<(?:script|style|link)/i,Ct=/^(?:checkbox|radio)$/i,Nt=/checked\s*(?:[^=]|=\s*.checked.)/i,kt=/^$|\/(?:java|ecma)script/i,Et=/^true\/(.*)/,St=/^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g,At={option:[1,"<select multiple='multiple'>","</select>"],legend:[1,"<fieldset>","</fieldset>"],area:[1,"<map>","</map>"],param:[1,"<object>","</object>"],thead:[1,"<table>","</table>"],tr:[2,"<table><tbody>","</tbody></table>"],col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],_default:x.support.htmlSerialize?[0,"",""]:[1,"X<div>","</div>"]},jt=dt(a),Dt=jt.appendChild(a.createElement("div"));At.optgroup=At.option,At.tbody=At.tfoot=At.colgroup=At.caption=At.thead,At.th=At.td,x.fn.extend({text:function(e){return x.access(this,function(e){return e===t?x.text(this):this.empty().append((this[0]&&this[0].ownerDocument||a).createTextNode(e))},null,e,arguments.length)},append:function(){return this.domManip(arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=Lt(this,e);t.appendChild(e)}})},prepend:function(){return this.domManip(arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=Lt(this,e);t.insertBefore(e,t.firstChild)}})},before:function(){return this.domManip(arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this)})},after:function(){return this.domManip(arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this.nextSibling)})},remove:function(e,t){var n,r=e?x.filter(e,this):this,i=0;for(;null!=(n=r[i]);i++)t||1!==n.nodeType||x.cleanData(Ft(n)),n.parentNode&&(t&&x.contains(n.ownerDocument,n)&&_t(Ft(n,"script")),n.parentNode.removeChild(n));return this},empty:function(){var e,t=0;for(;null!=(e=this[t]);t++){1===e.nodeType&&x.cleanData(Ft(e,!1));while(e.firstChild)e.removeChild(e.firstChild);e.options&&x.nodeName(e,"select")&&(e.options.length=0)}return this},clone:function(e,t){return e=null==e?!1:e,t=null==t?e:t,this.map(function(){return x.clone(this,e,t)})},html:function(e){return x.access(this,function(e){var n=this[0]||{},r=0,i=this.length;if(e===t)return 1===n.nodeType?n.innerHTML.replace(gt,""):t;if(!("string"!=typeof e||Tt.test(e)||!x.support.htmlSerialize&&mt.test(e)||!x.support.leadingWhitespace&&yt.test(e)||At[(bt.exec(e)||["",""])[1].toLowerCase()])){e=e.replace(vt,"<$1></$2>");try{for(;i>r;r++)n=this[r]||{},1===n.nodeType&&(x.cleanData(Ft(n,!1)),n.innerHTML=e);n=0}catch(o){}}n&&this.empty().append(e)},null,e,arguments.length)},replaceWith:function(){var e=x.map(this,function(e){return[e.nextSibling,e.parentNode]}),t=0;return this.domManip(arguments,function(n){var r=e[t++],i=e[t++];i&&(r&&r.parentNode!==i&&(r=this.nextSibling),x(this).remove(),i.insertBefore(n,r))},!0),t?this:this.remove()},detach:function(e){return this.remove(e,!0)},domManip:function(e,t,n){e=d.apply([],e);var r,i,o,a,s,l,u=0,c=this.length,p=this,f=c-1,h=e[0],g=x.isFunction(h);if(g||!(1>=c||"string"!=typeof h||x.support.checkClone)&&Nt.test(h))return this.each(function(r){var i=p.eq(r);g&&(e[0]=h.call(this,r,i.html())),i.domManip(e,t,n)});if(c&&(l=x.buildFragment(e,this[0].ownerDocument,!1,!n&&this),r=l.firstChild,1===l.childNodes.length&&(l=r),r)){for(a=x.map(Ft(l,"script"),Ht),o=a.length;c>u;u++)i=l,u!==f&&(i=x.clone(i,!0,!0),o&&x.merge(a,Ft(i,"script"))),t.call(this[u],i,u);if(o)for(s=a[a.length-1].ownerDocument,x.map(a,qt),u=0;o>u;u++)i=a[u],kt.test(i.type||"")&&!x._data(i,"globalEval")&&x.contains(s,i)&&(i.src?x._evalUrl(i.src):x.globalEval((i.text||i.textContent||i.innerHTML||"").replace(St,"")));l=r=null}return this}});function Lt(e,t){return x.nodeName(e,"table")&&x.nodeName(1===t.nodeType?t:t.firstChild,"tr")?e.getElementsByTagName("tbody")[0]||e.appendChild(e.ownerDocument.createElement("tbody")):e}function Ht(e){return e.type=(null!==x.find.attr(e,"type"))+"/"+e.type,e}function qt(e){var t=Et.exec(e.type);return t?e.type=t[1]:e.removeAttribute("type"),e}function _t(e,t){var n,r=0;for(;null!=(n=e[r]);r++)x._data(n,"globalEval",!t||x._data(t[r],"globalEval"))}function Mt(e,t){if(1===t.nodeType&&x.hasData(e)){var n,r,i,o=x._data(e),a=x._data(t,o),s=o.events;if(s){delete a.handle,a.events={};for(n in s)for(r=0,i=s[n].length;i>r;r++)x.event.add(t,n,s[n][r])}a.data&&(a.data=x.extend({},a.data))}}function Ot(e,t){var n,r,i;if(1===t.nodeType){if(n=t.nodeName.toLowerCase(),!x.support.noCloneEvent&&t[x.expando]){i=x._data(t);for(r in i.events)x.removeEvent(t,r,i.handle);t.removeAttribute(x.expando)}"script"===n&&t.text!==e.text?(Ht(t).text=e.text,qt(t)):"object"===n?(t.parentNode&&(t.outerHTML=e.outerHTML),x.support.html5Clone&&e.innerHTML&&!x.trim(t.innerHTML)&&(t.innerHTML=e.innerHTML)):"input"===n&&Ct.test(e.type)?(t.defaultChecked=t.checked=e.checked,t.value!==e.value&&(t.value=e.value)):"option"===n?t.defaultSelected=t.selected=e.defaultSelected:("input"===n||"textarea"===n)&&(t.defaultValue=e.defaultValue)}}x.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(e,t){x.fn[e]=function(e){var n,r=0,i=[],o=x(e),a=o.length-1;for(;a>=r;r++)n=r===a?this:this.clone(!0),x(o[r])[t](n),h.apply(i,n.get());return this.pushStack(i)}});function Ft(e,n){var r,o,a=0,s=typeof e.getElementsByTagName!==i?e.getElementsByTagName(n||"*"):typeof e.querySelectorAll!==i?e.querySelectorAll(n||"*"):t;if(!s)for(s=[],r=e.childNodes||e;null!=(o=r[a]);a++)!n||x.nodeName(o,n)?s.push(o):x.merge(s,Ft(o,n));return n===t||n&&x.nodeName(e,n)?x.merge([e],s):s}function Bt(e){Ct.test(e.type)&&(e.defaultChecked=e.checked)}x.extend({clone:function(e,t,n){var r,i,o,a,s,l=x.contains(e.ownerDocument,e);if(x.support.html5Clone||x.isXMLDoc(e)||!mt.test("<"+e.nodeName+">")?o=e.cloneNode(!0):(Dt.innerHTML=e.outerHTML,Dt.removeChild(o=Dt.firstChild)),!(x.support.noCloneEvent&&x.support.noCloneChecked||1!==e.nodeType&&11!==e.nodeType||x.isXMLDoc(e)))for(r=Ft(o),s=Ft(e),a=0;null!=(i=s[a]);++a)r[a]&&Ot(i,r[a]);if(t)if(n)for(s=s||Ft(e),r=r||Ft(o),a=0;null!=(i=s[a]);a++)Mt(i,r[a]);else Mt(e,o);return r=Ft(o,"script"),r.length>0&&_t(r,!l&&Ft(e,"script")),r=s=i=null,o},buildFragment:function(e,t,n,r){var i,o,a,s,l,u,c,p=e.length,f=dt(t),d=[],h=0;for(;p>h;h++)if(o=e[h],o||0===o)if("object"===x.type(o))x.merge(d,o.nodeType?[o]:o);else if(wt.test(o)){s=s||f.appendChild(t.createElement("div")),l=(bt.exec(o)||["",""])[1].toLowerCase(),c=At[l]||At._default,s.innerHTML=c[1]+o.replace(vt,"<$1></$2>")+c[2],i=c[0];while(i--)s=s.lastChild;if(!x.support.leadingWhitespace&&yt.test(o)&&d.push(t.createTextNode(yt.exec(o)[0])),!x.support.tbody){o="table"!==l||xt.test(o)?"<table>"!==c[1]||xt.test(o)?0:s:s.firstChild,i=o&&o.childNodes.length;while(i--)x.nodeName(u=o.childNodes[i],"tbody")&&!u.childNodes.length&&o.removeChild(u)}x.merge(d,s.childNodes),s.textContent="";while(s.firstChild)s.removeChild(s.firstChild);s=f.lastChild}else d.push(t.createTextNode(o));s&&f.removeChild(s),x.support.appendChecked||x.grep(Ft(d,"input"),Bt),h=0;while(o=d[h++])if((!r||-1===x.inArray(o,r))&&(a=x.contains(o.ownerDocument,o),s=Ft(f.appendChild(o),"script"),a&&_t(s),n)){i=0;while(o=s[i++])kt.test(o.type||"")&&n.push(o)}return s=null,f},cleanData:function(e,t){var n,r,o,a,s=0,l=x.expando,u=x.cache,c=x.support.deleteExpando,f=x.event.special;for(;null!=(n=e[s]);s++)if((t||x.acceptData(n))&&(o=n[l],a=o&&u[o])){if(a.events)for(r in a.events)f[r]?x.event.remove(n,r):x.removeEvent(n,r,a.handle);
	u[o]&&(delete u[o],c?delete n[l]:typeof n.removeAttribute!==i?n.removeAttribute(l):n[l]=null,p.push(o))}},_evalUrl:function(e){return x.ajax({url:e,type:"GET",dataType:"script",async:!1,global:!1,"throws":!0})}}),x.fn.extend({wrapAll:function(e){if(x.isFunction(e))return this.each(function(t){x(this).wrapAll(e.call(this,t))});if(this[0]){var t=x(e,this[0].ownerDocument).eq(0).clone(!0);this[0].parentNode&&t.insertBefore(this[0]),t.map(function(){var e=this;while(e.firstChild&&1===e.firstChild.nodeType)e=e.firstChild;return e}).append(this)}return this},wrapInner:function(e){return x.isFunction(e)?this.each(function(t){x(this).wrapInner(e.call(this,t))}):this.each(function(){var t=x(this),n=t.contents();n.length?n.wrapAll(e):t.append(e)})},wrap:function(e){var t=x.isFunction(e);return this.each(function(n){x(this).wrapAll(t?e.call(this,n):e)})},unwrap:function(){return this.parent().each(function(){x.nodeName(this,"body")||x(this).replaceWith(this.childNodes)}).end()}});var Pt,Rt,Wt,$t=/alpha\([^)]*\)/i,It=/opacity\s*=\s*([^)]*)/,zt=/^(top|right|bottom|left)$/,Xt=/^(none|table(?!-c[ea]).+)/,Ut=/^margin/,Vt=RegExp("^("+w+")(.*)$","i"),Yt=RegExp("^("+w+")(?!px)[a-z%]+$","i"),Jt=RegExp("^([+-])=("+w+")","i"),Gt={BODY:"block"},Qt={position:"absolute",visibility:"hidden",display:"block"},Kt={letterSpacing:0,fontWeight:400},Zt=["Top","Right","Bottom","Left"],en=["Webkit","O","Moz","ms"];function tn(e,t){if(t in e)return t;var n=t.charAt(0).toUpperCase()+t.slice(1),r=t,i=en.length;while(i--)if(t=en[i]+n,t in e)return t;return r}function nn(e,t){return e=t||e,"none"===x.css(e,"display")||!x.contains(e.ownerDocument,e)}function rn(e,t){var n,r,i,o=[],a=0,s=e.length;for(;s>a;a++)r=e[a],r.style&&(o[a]=x._data(r,"olddisplay"),n=r.style.display,t?(o[a]||"none"!==n||(r.style.display=""),""===r.style.display&&nn(r)&&(o[a]=x._data(r,"olddisplay",ln(r.nodeName)))):o[a]||(i=nn(r),(n&&"none"!==n||!i)&&x._data(r,"olddisplay",i?n:x.css(r,"display"))));for(a=0;s>a;a++)r=e[a],r.style&&(t&&"none"!==r.style.display&&""!==r.style.display||(r.style.display=t?o[a]||"":"none"));return e}x.fn.extend({css:function(e,n){return x.access(this,function(e,n,r){var i,o,a={},s=0;if(x.isArray(n)){for(o=Rt(e),i=n.length;i>s;s++)a[n[s]]=x.css(e,n[s],!1,o);return a}return r!==t?x.style(e,n,r):x.css(e,n)},e,n,arguments.length>1)},show:function(){return rn(this,!0)},hide:function(){return rn(this)},toggle:function(e){return"boolean"==typeof e?e?this.show():this.hide():this.each(function(){nn(this)?x(this).show():x(this).hide()})}}),x.extend({cssHooks:{opacity:{get:function(e,t){if(t){var n=Wt(e,"opacity");return""===n?"1":n}}}},cssNumber:{columnCount:!0,fillOpacity:!0,fontWeight:!0,lineHeight:!0,opacity:!0,order:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},cssProps:{"float":x.support.cssFloat?"cssFloat":"styleFloat"},style:function(e,n,r,i){if(e&&3!==e.nodeType&&8!==e.nodeType&&e.style){var o,a,s,l=x.camelCase(n),u=e.style;if(n=x.cssProps[l]||(x.cssProps[l]=tn(u,l)),s=x.cssHooks[n]||x.cssHooks[l],r===t)return s&&"get"in s&&(o=s.get(e,!1,i))!==t?o:u[n];if(a=typeof r,"string"===a&&(o=Jt.exec(r))&&(r=(o[1]+1)*o[2]+parseFloat(x.css(e,n)),a="number"),!(null==r||"number"===a&&isNaN(r)||("number"!==a||x.cssNumber[l]||(r+="px"),x.support.clearCloneStyle||""!==r||0!==n.indexOf("background")||(u[n]="inherit"),s&&"set"in s&&(r=s.set(e,r,i))===t)))try{u[n]=r}catch(c){}}},css:function(e,n,r,i){var o,a,s,l=x.camelCase(n);return n=x.cssProps[l]||(x.cssProps[l]=tn(e.style,l)),s=x.cssHooks[n]||x.cssHooks[l],s&&"get"in s&&(a=s.get(e,!0,r)),a===t&&(a=Wt(e,n,i)),"normal"===a&&n in Kt&&(a=Kt[n]),""===r||r?(o=parseFloat(a),r===!0||x.isNumeric(o)?o||0:a):a}}),e.getComputedStyle?(Rt=function(t){return e.getComputedStyle(t,null)},Wt=function(e,n,r){var i,o,a,s=r||Rt(e),l=s?s.getPropertyValue(n)||s[n]:t,u=e.style;return s&&(""!==l||x.contains(e.ownerDocument,e)||(l=x.style(e,n)),Yt.test(l)&&Ut.test(n)&&(i=u.width,o=u.minWidth,a=u.maxWidth,u.minWidth=u.maxWidth=u.width=l,l=s.width,u.width=i,u.minWidth=o,u.maxWidth=a)),l}):a.documentElement.currentStyle&&(Rt=function(e){return e.currentStyle},Wt=function(e,n,r){var i,o,a,s=r||Rt(e),l=s?s[n]:t,u=e.style;return null==l&&u&&u[n]&&(l=u[n]),Yt.test(l)&&!zt.test(n)&&(i=u.left,o=e.runtimeStyle,a=o&&o.left,a&&(o.left=e.currentStyle.left),u.left="fontSize"===n?"1em":l,l=u.pixelLeft+"px",u.left=i,a&&(o.left=a)),""===l?"auto":l});function on(e,t,n){var r=Vt.exec(t);return r?Math.max(0,r[1]-(n||0))+(r[2]||"px"):t}function an(e,t,n,r,i){var o=n===(r?"border":"content")?4:"width"===t?1:0,a=0;for(;4>o;o+=2)"margin"===n&&(a+=x.css(e,n+Zt[o],!0,i)),r?("content"===n&&(a-=x.css(e,"padding"+Zt[o],!0,i)),"margin"!==n&&(a-=x.css(e,"border"+Zt[o]+"Width",!0,i))):(a+=x.css(e,"padding"+Zt[o],!0,i),"padding"!==n&&(a+=x.css(e,"border"+Zt[o]+"Width",!0,i)));return a}function sn(e,t,n){var r=!0,i="width"===t?e.offsetWidth:e.offsetHeight,o=Rt(e),a=x.support.boxSizing&&"border-box"===x.css(e,"boxSizing",!1,o);if(0>=i||null==i){if(i=Wt(e,t,o),(0>i||null==i)&&(i=e.style[t]),Yt.test(i))return i;r=a&&(x.support.boxSizingReliable||i===e.style[t]),i=parseFloat(i)||0}return i+an(e,t,n||(a?"border":"content"),r,o)+"px"}function ln(e){var t=a,n=Gt[e];return n||(n=un(e,t),"none"!==n&&n||(Pt=(Pt||x("<iframe frameborder='0' width='0' height='0'/>").css("cssText","display:block !important")).appendTo(t.documentElement),t=(Pt[0].contentWindow||Pt[0].contentDocument).document,t.write("<!doctype html><html><body>"),t.close(),n=un(e,t),Pt.detach()),Gt[e]=n),n}function un(e,t){var n=x(t.createElement(e)).appendTo(t.body),r=x.css(n[0],"display");return n.remove(),r}x.each(["height","width"],function(e,n){x.cssHooks[n]={get:function(e,r,i){return r?0===e.offsetWidth&&Xt.test(x.css(e,"display"))?x.swap(e,Qt,function(){return sn(e,n,i)}):sn(e,n,i):t},set:function(e,t,r){var i=r&&Rt(e);return on(e,t,r?an(e,n,r,x.support.boxSizing&&"border-box"===x.css(e,"boxSizing",!1,i),i):0)}}}),x.support.opacity||(x.cssHooks.opacity={get:function(e,t){return It.test((t&&e.currentStyle?e.currentStyle.filter:e.style.filter)||"")?.01*parseFloat(RegExp.$1)+"":t?"1":""},set:function(e,t){var n=e.style,r=e.currentStyle,i=x.isNumeric(t)?"alpha(opacity="+100*t+")":"",o=r&&r.filter||n.filter||"";n.zoom=1,(t>=1||""===t)&&""===x.trim(o.replace($t,""))&&n.removeAttribute&&(n.removeAttribute("filter"),""===t||r&&!r.filter)||(n.filter=$t.test(o)?o.replace($t,i):o+" "+i)}}),x(function(){x.support.reliableMarginRight||(x.cssHooks.marginRight={get:function(e,n){return n?x.swap(e,{display:"inline-block"},Wt,[e,"marginRight"]):t}}),!x.support.pixelPosition&&x.fn.position&&x.each(["top","left"],function(e,n){x.cssHooks[n]={get:function(e,r){return r?(r=Wt(e,n),Yt.test(r)?x(e).position()[n]+"px":r):t}}})}),x.expr&&x.expr.filters&&(x.expr.filters.hidden=function(e){return 0>=e.offsetWidth&&0>=e.offsetHeight||!x.support.reliableHiddenOffsets&&"none"===(e.style&&e.style.display||x.css(e,"display"))},x.expr.filters.visible=function(e){return!x.expr.filters.hidden(e)}),x.each({margin:"",padding:"",border:"Width"},function(e,t){x.cssHooks[e+t]={expand:function(n){var r=0,i={},o="string"==typeof n?n.split(" "):[n];for(;4>r;r++)i[e+Zt[r]+t]=o[r]||o[r-2]||o[0];return i}},Ut.test(e)||(x.cssHooks[e+t].set=on)});var cn=/%20/g,pn=/\[\]$/,fn=/\r?\n/g,dn=/^(?:submit|button|image|reset|file)$/i,hn=/^(?:input|select|textarea|keygen)/i;x.fn.extend({serialize:function(){return x.param(this.serializeArray())},serializeArray:function(){return this.map(function(){var e=x.prop(this,"elements");return e?x.makeArray(e):this}).filter(function(){var e=this.type;return this.name&&!x(this).is(":disabled")&&hn.test(this.nodeName)&&!dn.test(e)&&(this.checked||!Ct.test(e))}).map(function(e,t){var n=x(this).val();return null==n?null:x.isArray(n)?x.map(n,function(e){return{name:t.name,value:e.replace(fn,"\r\n")}}):{name:t.name,value:n.replace(fn,"\r\n")}}).get()}}),x.param=function(e,n){var r,i=[],o=function(e,t){t=x.isFunction(t)?t():null==t?"":t,i[i.length]=encodeURIComponent(e)+"="+encodeURIComponent(t)};if(n===t&&(n=x.ajaxSettings&&x.ajaxSettings.traditional),x.isArray(e)||e.jquery&&!x.isPlainObject(e))x.each(e,function(){o(this.name,this.value)});else for(r in e)gn(r,e[r],n,o);return i.join("&").replace(cn,"+")};function gn(e,t,n,r){var i;if(x.isArray(t))x.each(t,function(t,i){n||pn.test(e)?r(e,i):gn(e+"["+("object"==typeof i?t:"")+"]",i,n,r)});else if(n||"object"!==x.type(t))r(e,t);else for(i in t)gn(e+"["+i+"]",t[i],n,r)}x.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error contextmenu".split(" "),function(e,t){x.fn[t]=function(e,n){return arguments.length>0?this.on(t,null,e,n):this.trigger(t)}}),x.fn.extend({hover:function(e,t){return this.mouseenter(e).mouseleave(t||e)},bind:function(e,t,n){return this.on(e,null,t,n)},unbind:function(e,t){return this.off(e,null,t)},delegate:function(e,t,n,r){return this.on(t,e,n,r)},undelegate:function(e,t,n){return 1===arguments.length?this.off(e,"**"):this.off(t,e||"**",n)}});var mn,yn,vn=x.now(),bn=/\?/,xn=/#.*$/,wn=/([?&])_=[^&]*/,Tn=/^(.*?):[ \t]*([^\r\n]*)\r?$/gm,Cn=/^(?:about|app|app-storage|.+-extension|file|res|widget):$/,Nn=/^(?:GET|HEAD)$/,kn=/^\/\//,En=/^([\w.+-]+:)(?:\/\/([^\/?#:]*)(?::(\d+)|)|)/,Sn=x.fn.load,An={},jn={},Dn="*/".concat("*");try{yn=o.href}catch(Ln){yn=a.createElement("a"),yn.href="",yn=yn.href}mn=En.exec(yn.toLowerCase())||[];function Hn(e){return function(t,n){"string"!=typeof t&&(n=t,t="*");var r,i=0,o=t.toLowerCase().match(T)||[];if(x.isFunction(n))while(r=o[i++])"+"===r[0]?(r=r.slice(1)||"*",(e[r]=e[r]||[]).unshift(n)):(e[r]=e[r]||[]).push(n)}}function qn(e,n,r,i){var o={},a=e===jn;function s(l){var u;return o[l]=!0,x.each(e[l]||[],function(e,l){var c=l(n,r,i);return"string"!=typeof c||a||o[c]?a?!(u=c):t:(n.dataTypes.unshift(c),s(c),!1)}),u}return s(n.dataTypes[0])||!o["*"]&&s("*")}function _n(e,n){var r,i,o=x.ajaxSettings.flatOptions||{};for(i in n)n[i]!==t&&((o[i]?e:r||(r={}))[i]=n[i]);return r&&x.extend(!0,e,r),e}x.fn.load=function(e,n,r){if("string"!=typeof e&&Sn)return Sn.apply(this,arguments);var i,o,a,s=this,l=e.indexOf(" ");return l>=0&&(i=e.slice(l,e.length),e=e.slice(0,l)),x.isFunction(n)?(r=n,n=t):n&&"object"==typeof n&&(a="POST"),s.length>0&&x.ajax({url:e,type:a,dataType:"html",data:n}).done(function(e){o=arguments,s.html(i?x("<div>").append(x.parseHTML(e)).find(i):e)}).complete(r&&function(e,t){s.each(r,o||[e.responseText,t,e])}),this},x.each(["ajaxStart","ajaxStop","ajaxComplete","ajaxError","ajaxSuccess","ajaxSend"],function(e,t){x.fn[t]=function(e){return this.on(t,e)}}),x.extend({active:0,lastModified:{},etag:{},ajaxSettings:{url:yn,type:"GET",isLocal:Cn.test(mn[1]),global:!0,processData:!0,async:!0,contentType:"application/x-www-form-urlencoded; charset=UTF-8",accepts:{"*":Dn,text:"text/plain",html:"text/html",xml:"application/xml, text/xml",json:"application/json, text/javascript"},contents:{xml:/xml/,html:/html/,json:/json/},responseFields:{xml:"responseXML",text:"responseText",json:"responseJSON"},converters:{"* text":String,"text html":!0,"text json":x.parseJSON,"text xml":x.parseXML},flatOptions:{url:!0,context:!0}},ajaxSetup:function(e,t){return t?_n(_n(e,x.ajaxSettings),t):_n(x.ajaxSettings,e)},ajaxPrefilter:Hn(An),ajaxTransport:Hn(jn),ajax:function(e,n){"object"==typeof e&&(n=e,e=t),n=n||{};var r,i,o,a,s,l,u,c,p=x.ajaxSetup({},n),f=p.context||p,d=p.context&&(f.nodeType||f.jquery)?x(f):x.event,h=x.Deferred(),g=x.Callbacks("once memory"),m=p.statusCode||{},y={},v={},b=0,w="canceled",C={readyState:0,getResponseHeader:function(e){var t;if(2===b){if(!c){c={};while(t=Tn.exec(a))c[t[1].toLowerCase()]=t[2]}t=c[e.toLowerCase()]}return null==t?null:t},getAllResponseHeaders:function(){return 2===b?a:null},setRequestHeader:function(e,t){var n=e.toLowerCase();return b||(e=v[n]=v[n]||e,y[e]=t),this},overrideMimeType:function(e){return b||(p.mimeType=e),this},statusCode:function(e){var t;if(e)if(2>b)for(t in e)m[t]=[m[t],e[t]];else C.always(e[C.status]);return this},abort:function(e){var t=e||w;return u&&u.abort(t),k(0,t),this}};if(h.promise(C).complete=g.add,C.success=C.done,C.error=C.fail,p.url=((e||p.url||yn)+"").replace(xn,"").replace(kn,mn[1]+"//"),p.type=n.method||n.type||p.method||p.type,p.dataTypes=x.trim(p.dataType||"*").toLowerCase().match(T)||[""],null==p.crossDomain&&(r=En.exec(p.url.toLowerCase()),p.crossDomain=!(!r||r[1]===mn[1]&&r[2]===mn[2]&&(r[3]||("http:"===r[1]?"80":"443"))===(mn[3]||("http:"===mn[1]?"80":"443")))),p.data&&p.processData&&"string"!=typeof p.data&&(p.data=x.param(p.data,p.traditional)),qn(An,p,n,C),2===b)return C;l=p.global,l&&0===x.active++&&x.event.trigger("ajaxStart"),p.type=p.type.toUpperCase(),p.hasContent=!Nn.test(p.type),o=p.url,p.hasContent||(p.data&&(o=p.url+=(bn.test(o)?"&":"?")+p.data,delete p.data),p.cache===!1&&(p.url=wn.test(o)?o.replace(wn,"$1_="+vn++):o+(bn.test(o)?"&":"?")+"_="+vn++)),p.ifModified&&(x.lastModified[o]&&C.setRequestHeader("If-Modified-Since",x.lastModified[o]),x.etag[o]&&C.setRequestHeader("If-None-Match",x.etag[o])),(p.data&&p.hasContent&&p.contentType!==!1||n.contentType)&&C.setRequestHeader("Content-Type",p.contentType),C.setRequestHeader("Accept",p.dataTypes[0]&&p.accepts[p.dataTypes[0]]?p.accepts[p.dataTypes[0]]+("*"!==p.dataTypes[0]?", "+Dn+"; q=0.01":""):p.accepts["*"]);for(i in p.headers)C.setRequestHeader(i,p.headers[i]);if(p.beforeSend&&(p.beforeSend.call(f,C,p)===!1||2===b))return C.abort();w="abort";for(i in{success:1,error:1,complete:1})C[i](p[i]);if(u=qn(jn,p,n,C)){C.readyState=1,l&&d.trigger("ajaxSend",[C,p]),p.async&&p.timeout>0&&(s=setTimeout(function(){C.abort("timeout")},p.timeout));try{b=1,u.send(y,k)}catch(N){if(!(2>b))throw N;k(-1,N)}}else k(-1,"No Transport");function k(e,n,r,i){var c,y,v,w,T,N=n;2!==b&&(b=2,s&&clearTimeout(s),u=t,a=i||"",C.readyState=e>0?4:0,c=e>=200&&300>e||304===e,r&&(w=Mn(p,C,r)),w=On(p,w,C,c),c?(p.ifModified&&(T=C.getResponseHeader("Last-Modified"),T&&(x.lastModified[o]=T),T=C.getResponseHeader("etag"),T&&(x.etag[o]=T)),204===e||"HEAD"===p.type?N="nocontent":304===e?N="notmodified":(N=w.state,y=w.data,v=w.error,c=!v)):(v=N,(e||!N)&&(N="error",0>e&&(e=0))),C.status=e,C.statusText=(n||N)+"",c?h.resolveWith(f,[y,N,C]):h.rejectWith(f,[C,N,v]),C.statusCode(m),m=t,l&&d.trigger(c?"ajaxSuccess":"ajaxError",[C,p,c?y:v]),g.fireWith(f,[C,N]),l&&(d.trigger("ajaxComplete",[C,p]),--x.active||x.event.trigger("ajaxStop")))}return C},getJSON:function(e,t,n){return x.get(e,t,n,"json")},getScript:function(e,n){return x.get(e,t,n,"script")}}),x.each(["get","post"],function(e,n){x[n]=function(e,r,i,o){return x.isFunction(r)&&(o=o||i,i=r,r=t),x.ajax({url:e,type:n,dataType:o,data:r,success:i})}});function Mn(e,n,r){var i,o,a,s,l=e.contents,u=e.dataTypes;while("*"===u[0])u.shift(),o===t&&(o=e.mimeType||n.getResponseHeader("Content-Type"));if(o)for(s in l)if(l[s]&&l[s].test(o)){u.unshift(s);break}if(u[0]in r)a=u[0];else{for(s in r){if(!u[0]||e.converters[s+" "+u[0]]){a=s;break}i||(i=s)}a=a||i}return a?(a!==u[0]&&u.unshift(a),r[a]):t}function On(e,t,n,r){var i,o,a,s,l,u={},c=e.dataTypes.slice();if(c[1])for(a in e.converters)u[a.toLowerCase()]=e.converters[a];o=c.shift();while(o)if(e.responseFields[o]&&(n[e.responseFields[o]]=t),!l&&r&&e.dataFilter&&(t=e.dataFilter(t,e.dataType)),l=o,o=c.shift())if("*"===o)o=l;else if("*"!==l&&l!==o){if(a=u[l+" "+o]||u["* "+o],!a)for(i in u)if(s=i.split(" "),s[1]===o&&(a=u[l+" "+s[0]]||u["* "+s[0]])){a===!0?a=u[i]:u[i]!==!0&&(o=s[0],c.unshift(s[1]));break}if(a!==!0)if(a&&e["throws"])t=a(t);else try{t=a(t)}catch(p){return{state:"parsererror",error:a?p:"No conversion from "+l+" to "+o}}}return{state:"success",data:t}}x.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/(?:java|ecma)script/},converters:{"text script":function(e){return x.globalEval(e),e}}}),x.ajaxPrefilter("script",function(e){e.cache===t&&(e.cache=!1),e.crossDomain&&(e.type="GET",e.global=!1)}),x.ajaxTransport("script",function(e){if(e.crossDomain){var n,r=a.head||x("head")[0]||a.documentElement;return{send:function(t,i){n=a.createElement("script"),n.async=!0,e.scriptCharset&&(n.charset=e.scriptCharset),n.src=e.url,n.onload=n.onreadystatechange=function(e,t){(t||!n.readyState||/loaded|complete/.test(n.readyState))&&(n.onload=n.onreadystatechange=null,n.parentNode&&n.parentNode.removeChild(n),n=null,t||i(200,"success"))},r.insertBefore(n,r.firstChild)},abort:function(){n&&n.onload(t,!0)}}}});var Fn=[],Bn=/(=)\?(?=&|$)|\?\?/;x.ajaxSetup({jsonp:"callback",jsonpCallback:function(){var e=Fn.pop()||x.expando+"_"+vn++;return this[e]=!0,e}}),x.ajaxPrefilter("json jsonp",function(n,r,i){var o,a,s,l=n.jsonp!==!1&&(Bn.test(n.url)?"url":"string"==typeof n.data&&!(n.contentType||"").indexOf("application/x-www-form-urlencoded")&&Bn.test(n.data)&&"data");return l||"jsonp"===n.dataTypes[0]?(o=n.jsonpCallback=x.isFunction(n.jsonpCallback)?n.jsonpCallback():n.jsonpCallback,l?n[l]=n[l].replace(Bn,"$1"+o):n.jsonp!==!1&&(n.url+=(bn.test(n.url)?"&":"?")+n.jsonp+"="+o),n.converters["script json"]=function(){return s||x.error(o+" was not called"),s[0]},n.dataTypes[0]="json",a=e[o],e[o]=function(){s=arguments},i.always(function(){e[o]=a,n[o]&&(n.jsonpCallback=r.jsonpCallback,Fn.push(o)),s&&x.isFunction(a)&&a(s[0]),s=a=t}),"script"):t});var Pn,Rn,Wn=0,$n=e.ActiveXObject&&function(){var e;for(e in Pn)Pn[e](t,!0)};function In(){try{return new e.XMLHttpRequest}catch(t){}}function zn(){try{return new e.ActiveXObject("Microsoft.XMLHTTP")}catch(t){}}x.ajaxSettings.xhr=e.ActiveXObject?function(){return!this.isLocal&&In()||zn()}:In,Rn=x.ajaxSettings.xhr(),x.support.cors=!!Rn&&"withCredentials"in Rn,Rn=x.support.ajax=!!Rn,Rn&&x.ajaxTransport(function(n){if(!n.crossDomain||x.support.cors){var r;return{send:function(i,o){var a,s,l=n.xhr();if(n.username?l.open(n.type,n.url,n.async,n.username,n.password):l.open(n.type,n.url,n.async),n.xhrFields)for(s in n.xhrFields)l[s]=n.xhrFields[s];n.mimeType&&l.overrideMimeType&&l.overrideMimeType(n.mimeType),n.crossDomain||i["X-Requested-With"]||(i["X-Requested-With"]="XMLHttpRequest");try{for(s in i)l.setRequestHeader(s,i[s])}catch(u){}l.send(n.hasContent&&n.data||null),r=function(e,i){var s,u,c,p;try{if(r&&(i||4===l.readyState))if(r=t,a&&(l.onreadystatechange=x.noop,$n&&delete Pn[a]),i)4!==l.readyState&&l.abort();else{p={},s=l.status,u=l.getAllResponseHeaders(),"string"==typeof l.responseText&&(p.text=l.responseText);try{c=l.statusText}catch(f){c=""}s||!n.isLocal||n.crossDomain?1223===s&&(s=204):s=p.text?200:404}}catch(d){i||o(-1,d)}p&&o(s,c,p,u)},n.async?4===l.readyState?setTimeout(r):(a=++Wn,$n&&(Pn||(Pn={},x(e).unload($n)),Pn[a]=r),l.onreadystatechange=r):r()},abort:function(){r&&r(t,!0)}}}});var Xn,Un,Vn=/^(?:toggle|show|hide)$/,Yn=RegExp("^(?:([+-])=|)("+w+")([a-z%]*)$","i"),Jn=/queueHooks$/,Gn=[nr],Qn={"*":[function(e,t){var n=this.createTween(e,t),r=n.cur(),i=Yn.exec(t),o=i&&i[3]||(x.cssNumber[e]?"":"px"),a=(x.cssNumber[e]||"px"!==o&&+r)&&Yn.exec(x.css(n.elem,e)),s=1,l=20;if(a&&a[3]!==o){o=o||a[3],i=i||[],a=+r||1;do s=s||".5",a/=s,x.style(n.elem,e,a+o);while(s!==(s=n.cur()/r)&&1!==s&&--l)}return i&&(a=n.start=+a||+r||0,n.unit=o,n.end=i[1]?a+(i[1]+1)*i[2]:+i[2]),n}]};function Kn(){return setTimeout(function(){Xn=t}),Xn=x.now()}function Zn(e,t,n){var r,i=(Qn[t]||[]).concat(Qn["*"]),o=0,a=i.length;for(;a>o;o++)if(r=i[o].call(n,t,e))return r}function er(e,t,n){var r,i,o=0,a=Gn.length,s=x.Deferred().always(function(){delete l.elem}),l=function(){if(i)return!1;var t=Xn||Kn(),n=Math.max(0,u.startTime+u.duration-t),r=n/u.duration||0,o=1-r,a=0,l=u.tweens.length;for(;l>a;a++)u.tweens[a].run(o);return s.notifyWith(e,[u,o,n]),1>o&&l?n:(s.resolveWith(e,[u]),!1)},u=s.promise({elem:e,props:x.extend({},t),opts:x.extend(!0,{specialEasing:{}},n),originalProperties:t,originalOptions:n,startTime:Xn||Kn(),duration:n.duration,tweens:[],createTween:function(t,n){var r=x.Tween(e,u.opts,t,n,u.opts.specialEasing[t]||u.opts.easing);return u.tweens.push(r),r},stop:function(t){var n=0,r=t?u.tweens.length:0;if(i)return this;for(i=!0;r>n;n++)u.tweens[n].run(1);return t?s.resolveWith(e,[u,t]):s.rejectWith(e,[u,t]),this}}),c=u.props;for(tr(c,u.opts.specialEasing);a>o;o++)if(r=Gn[o].call(u,e,c,u.opts))return r;return x.map(c,Zn,u),x.isFunction(u.opts.start)&&u.opts.start.call(e,u),x.fx.timer(x.extend(l,{elem:e,anim:u,queue:u.opts.queue})),u.progress(u.opts.progress).done(u.opts.done,u.opts.complete).fail(u.opts.fail).always(u.opts.always)}function tr(e,t){var n,r,i,o,a;for(n in e)if(r=x.camelCase(n),i=t[r],o=e[n],x.isArray(o)&&(i=o[1],o=e[n]=o[0]),n!==r&&(e[r]=o,delete e[n]),a=x.cssHooks[r],a&&"expand"in a){o=a.expand(o),delete e[r];for(n in o)n in e||(e[n]=o[n],t[n]=i)}else t[r]=i}x.Animation=x.extend(er,{tweener:function(e,t){x.isFunction(e)?(t=e,e=["*"]):e=e.split(" ");var n,r=0,i=e.length;for(;i>r;r++)n=e[r],Qn[n]=Qn[n]||[],Qn[n].unshift(t)},prefilter:function(e,t){t?Gn.unshift(e):Gn.push(e)}});function nr(e,t,n){var r,i,o,a,s,l,u=this,c={},p=e.style,f=e.nodeType&&nn(e),d=x._data(e,"fxshow");n.queue||(s=x._queueHooks(e,"fx"),null==s.unqueued&&(s.unqueued=0,l=s.empty.fire,s.empty.fire=function(){s.unqueued||l()}),s.unqueued++,u.always(function(){u.always(function(){s.unqueued--,x.queue(e,"fx").length||s.empty.fire()})})),1===e.nodeType&&("height"in t||"width"in t)&&(n.overflow=[p.overflow,p.overflowX,p.overflowY],"inline"===x.css(e,"display")&&"none"===x.css(e,"float")&&(x.support.inlineBlockNeedsLayout&&"inline"!==ln(e.nodeName)?p.zoom=1:p.display="inline-block")),n.overflow&&(p.overflow="hidden",x.support.shrinkWrapBlocks||u.always(function(){p.overflow=n.overflow[0],p.overflowX=n.overflow[1],p.overflowY=n.overflow[2]}));for(r in t)if(i=t[r],Vn.exec(i)){if(delete t[r],o=o||"toggle"===i,i===(f?"hide":"show"))continue;c[r]=d&&d[r]||x.style(e,r)}if(!x.isEmptyObject(c)){d?"hidden"in d&&(f=d.hidden):d=x._data(e,"fxshow",{}),o&&(d.hidden=!f),f?x(e).show():u.done(function(){x(e).hide()}),u.done(function(){var t;x._removeData(e,"fxshow");for(t in c)x.style(e,t,c[t])});for(r in c)a=Zn(f?d[r]:0,r,u),r in d||(d[r]=a.start,f&&(a.end=a.start,a.start="width"===r||"height"===r?1:0))}}function rr(e,t,n,r,i){return new rr.prototype.init(e,t,n,r,i)}x.Tween=rr,rr.prototype={constructor:rr,init:function(e,t,n,r,i,o){this.elem=e,this.prop=n,this.easing=i||"swing",this.options=t,this.start=this.now=this.cur(),this.end=r,this.unit=o||(x.cssNumber[n]?"":"px")},cur:function(){var e=rr.propHooks[this.prop];return e&&e.get?e.get(this):rr.propHooks._default.get(this)},run:function(e){var t,n=rr.propHooks[this.prop];return this.pos=t=this.options.duration?x.easing[this.easing](e,this.options.duration*e,0,1,this.options.duration):e,this.now=(this.end-this.start)*t+this.start,this.options.step&&this.options.step.call(this.elem,this.now,this),n&&n.set?n.set(this):rr.propHooks._default.set(this),this}},rr.prototype.init.prototype=rr.prototype,rr.propHooks={_default:{get:function(e){var t;return null==e.elem[e.prop]||e.elem.style&&null!=e.elem.style[e.prop]?(t=x.css(e.elem,e.prop,""),t&&"auto"!==t?t:0):e.elem[e.prop]},set:function(e){x.fx.step[e.prop]?x.fx.step[e.prop](e):e.elem.style&&(null!=e.elem.style[x.cssProps[e.prop]]||x.cssHooks[e.prop])?x.style(e.elem,e.prop,e.now+e.unit):e.elem[e.prop]=e.now}}},rr.propHooks.scrollTop=rr.propHooks.scrollLeft={set:function(e){e.elem.nodeType&&e.elem.parentNode&&(e.elem[e.prop]=e.now)}},x.each(["toggle","show","hide"],function(e,t){var n=x.fn[t];x.fn[t]=function(e,r,i){return null==e||"boolean"==typeof e?n.apply(this,arguments):this.animate(ir(t,!0),e,r,i)}}),x.fn.extend({fadeTo:function(e,t,n,r){return this.filter(nn).css("opacity",0).show().end().animate({opacity:t},e,n,r)},animate:function(e,t,n,r){var i=x.isEmptyObject(e),o=x.speed(t,n,r),a=function(){var t=er(this,x.extend({},e),o);(i||x._data(this,"finish"))&&t.stop(!0)};return a.finish=a,i||o.queue===!1?this.each(a):this.queue(o.queue,a)},stop:function(e,n,r){var i=function(e){var t=e.stop;delete e.stop,t(r)};return"string"!=typeof e&&(r=n,n=e,e=t),n&&e!==!1&&this.queue(e||"fx",[]),this.each(function(){var t=!0,n=null!=e&&e+"queueHooks",o=x.timers,a=x._data(this);if(n)a[n]&&a[n].stop&&i(a[n]);else for(n in a)a[n]&&a[n].stop&&Jn.test(n)&&i(a[n]);for(n=o.length;n--;)o[n].elem!==this||null!=e&&o[n].queue!==e||(o[n].anim.stop(r),t=!1,o.splice(n,1));(t||!r)&&x.dequeue(this,e)})},finish:function(e){return e!==!1&&(e=e||"fx"),this.each(function(){var t,n=x._data(this),r=n[e+"queue"],i=n[e+"queueHooks"],o=x.timers,a=r?r.length:0;for(n.finish=!0,x.queue(this,e,[]),i&&i.stop&&i.stop.call(this,!0),t=o.length;t--;)o[t].elem===this&&o[t].queue===e&&(o[t].anim.stop(!0),o.splice(t,1));for(t=0;a>t;t++)r[t]&&r[t].finish&&r[t].finish.call(this);delete n.finish})}});function ir(e,t){var n,r={height:e},i=0;for(t=t?1:0;4>i;i+=2-t)n=Zt[i],r["margin"+n]=r["padding"+n]=e;return t&&(r.opacity=r.width=e),r}x.each({slideDown:ir("show"),slideUp:ir("hide"),slideToggle:ir("toggle"),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(e,t){x.fn[e]=function(e,n,r){return this.animate(t,e,n,r)}}),x.speed=function(e,t,n){var r=e&&"object"==typeof e?x.extend({},e):{complete:n||!n&&t||x.isFunction(e)&&e,duration:e,easing:n&&t||t&&!x.isFunction(t)&&t};return r.duration=x.fx.off?0:"number"==typeof r.duration?r.duration:r.duration in x.fx.speeds?x.fx.speeds[r.duration]:x.fx.speeds._default,(null==r.queue||r.queue===!0)&&(r.queue="fx"),r.old=r.complete,r.complete=function(){x.isFunction(r.old)&&r.old.call(this),r.queue&&x.dequeue(this,r.queue)},r},x.easing={linear:function(e){return e},swing:function(e){return.5-Math.cos(e*Math.PI)/2}},x.timers=[],x.fx=rr.prototype.init,x.fx.tick=function(){var e,n=x.timers,r=0;for(Xn=x.now();n.length>r;r++)e=n[r],e()||n[r]!==e||n.splice(r--,1);n.length||x.fx.stop(),Xn=t},x.fx.timer=function(e){e()&&x.timers.push(e)&&x.fx.start()},x.fx.interval=13,x.fx.start=function(){Un||(Un=setInterval(x.fx.tick,x.fx.interval))},x.fx.stop=function(){clearInterval(Un),Un=null},x.fx.speeds={slow:600,fast:200,_default:400},x.fx.step={},x.expr&&x.expr.filters&&(x.expr.filters.animated=function(e){return x.grep(x.timers,function(t){return e===t.elem}).length}),x.fn.offset=function(e){if(arguments.length)return e===t?this:this.each(function(t){x.offset.setOffset(this,e,t)});var n,r,o={top:0,left:0},a=this[0],s=a&&a.ownerDocument;if(s)return n=s.documentElement,x.contains(n,a)?(typeof a.getBoundingClientRect!==i&&(o=a.getBoundingClientRect()),r=or(s),{top:o.top+(r.pageYOffset||n.scrollTop)-(n.clientTop||0),left:o.left+(r.pageXOffset||n.scrollLeft)-(n.clientLeft||0)}):o},x.offset={setOffset:function(e,t,n){var r=x.css(e,"position");"static"===r&&(e.style.position="relative");var i=x(e),o=i.offset(),a=x.css(e,"top"),s=x.css(e,"left"),l=("absolute"===r||"fixed"===r)&&x.inArray("auto",[a,s])>-1,u={},c={},p,f;l?(c=i.position(),p=c.top,f=c.left):(p=parseFloat(a)||0,f=parseFloat(s)||0),x.isFunction(t)&&(t=t.call(e,n,o)),null!=t.top&&(u.top=t.top-o.top+p),null!=t.left&&(u.left=t.left-o.left+f),"using"in t?t.using.call(e,u):i.css(u)}},x.fn.extend({position:function(){if(this[0]){var e,t,n={top:0,left:0},r=this[0];return"fixed"===x.css(r,"position")?t=r.getBoundingClientRect():(e=this.offsetParent(),t=this.offset(),x.nodeName(e[0],"html")||(n=e.offset()),n.top+=x.css(e[0],"borderTopWidth",!0),n.left+=x.css(e[0],"borderLeftWidth",!0)),{top:t.top-n.top-x.css(r,"marginTop",!0),left:t.left-n.left-x.css(r,"marginLeft",!0)}}},offsetParent:function(){return this.map(function(){var e=this.offsetParent||s;while(e&&!x.nodeName(e,"html")&&"static"===x.css(e,"position"))e=e.offsetParent;return e||s})}}),x.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(e,n){var r=/Y/.test(n);x.fn[e]=function(i){return x.access(this,function(e,i,o){var a=or(e);return o===t?a?n in a?a[n]:a.document.documentElement[i]:e[i]:(a?a.scrollTo(r?x(a).scrollLeft():o,r?o:x(a).scrollTop()):e[i]=o,t)},e,i,arguments.length,null)}});function or(e){return x.isWindow(e)?e:9===e.nodeType?e.defaultView||e.parentWindow:!1}x.each({Height:"height",Width:"width"},function(e,n){x.each({padding:"inner"+e,content:n,"":"outer"+e},function(r,i){x.fn[i]=function(i,o){var a=arguments.length&&(r||"boolean"!=typeof i),s=r||(i===!0||o===!0?"margin":"border");return x.access(this,function(n,r,i){var o;return x.isWindow(n)?n.document.documentElement["client"+e]:9===n.nodeType?(o=n.documentElement,Math.max(n.body["scroll"+e],o["scroll"+e],n.body["offset"+e],o["offset"+e],o["client"+e])):i===t?x.css(n,r,s):x.style(n,r,i,s)},n,a?i:t,a,null)}})}),x.fn.size=function(){return this.length},x.fn.andSelf=x.fn.addBack,"object"==typeof module&&module&&"object"==typeof module.exports?module.exports=x:(e.jQuery=e.$=x,"function"==typeof define&&define.amd&&define("jquery",[],function(){return x}))})(window);
	</script>	


	<script type="text/javascript">
	/*! jQuery UI - v1.10.3 - 2013-12-21
	* http://jqueryui.com
	* Includes: jquery.ui.core.js, jquery.ui.widget.js, jquery.ui.mouse.js, jquery.ui.position.js, jquery.ui.draggable.js, jquery.ui.resizable.js, jquery.ui.button.js, jquery.ui.dialog.js, jquery.ui.progressbar.js, jquery.ui.effect.js, jquery.ui.effect-blind.js, jquery.ui.effect-clip.js, jquery.ui.effect-drop.js, jquery.ui.effect-fade.js, jquery.ui.effect-fold.js, jquery.ui.effect-highlight.js, jquery.ui.effect-scale.js, jquery.ui.effect-slide.js
	* Copyright 2013 jQuery Foundation and other contributors; Licensed MIT */

	(function(e,t){function i(t,i){var s,n,r,o=t.nodeName.toLowerCase();return"area"===o?(s=t.parentNode,n=s.name,t.href&&n&&"map"===s.nodeName.toLowerCase()?(r=e("img[usemap=#"+n+"]")[0],!!r&&a(r)):!1):(/input|select|textarea|button|object/.test(o)?!t.disabled:"a"===o?t.href||i:i)&&a(t)}function a(t){return e.expr.filters.visible(t)&&!e(t).parents().addBack().filter(function(){return"hidden"===e.css(this,"visibility")}).length}var s=0,n=/^ui-id-\d+$/;e.ui=e.ui||{},e.extend(e.ui,{version:"1.10.3",keyCode:{BACKSPACE:8,COMMA:188,DELETE:46,DOWN:40,END:35,ENTER:13,ESCAPE:27,HOME:36,LEFT:37,NUMPAD_ADD:107,NUMPAD_DECIMAL:110,NUMPAD_DIVIDE:111,NUMPAD_ENTER:108,NUMPAD_MULTIPLY:106,NUMPAD_SUBTRACT:109,PAGE_DOWN:34,PAGE_UP:33,PERIOD:190,RIGHT:39,SPACE:32,TAB:9,UP:38}}),e.fn.extend({focus:function(t){return function(i,a){return"number"==typeof i?this.each(function(){var t=this;setTimeout(function(){e(t).focus(),a&&a.call(t)},i)}):t.apply(this,arguments)}}(e.fn.focus),scrollParent:function(){var t;return t=e.ui.ie&&/(static|relative)/.test(this.css("position"))||/absolute/.test(this.css("position"))?this.parents().filter(function(){return/(relative|absolute|fixed)/.test(e.css(this,"position"))&&/(auto|scroll)/.test(e.css(this,"overflow")+e.css(this,"overflow-y")+e.css(this,"overflow-x"))}).eq(0):this.parents().filter(function(){return/(auto|scroll)/.test(e.css(this,"overflow")+e.css(this,"overflow-y")+e.css(this,"overflow-x"))}).eq(0),/fixed/.test(this.css("position"))||!t.length?e(document):t},zIndex:function(i){if(i!==t)return this.css("zIndex",i);if(this.length)for(var a,s,n=e(this[0]);n.length&&n[0]!==document;){if(a=n.css("position"),("absolute"===a||"relative"===a||"fixed"===a)&&(s=parseInt(n.css("zIndex"),10),!isNaN(s)&&0!==s))return s;n=n.parent()}return 0},uniqueId:function(){return this.each(function(){this.id||(this.id="ui-id-"+ ++s)})},removeUniqueId:function(){return this.each(function(){n.test(this.id)&&e(this).removeAttr("id")})}}),e.extend(e.expr[":"],{data:e.expr.createPseudo?e.expr.createPseudo(function(t){return function(i){return!!e.data(i,t)}}):function(t,i,a){return!!e.data(t,a[3])},focusable:function(t){return i(t,!isNaN(e.attr(t,"tabindex")))},tabbable:function(t){var a=e.attr(t,"tabindex"),s=isNaN(a);return(s||a>=0)&&i(t,!s)}}),e("<a>").outerWidth(1).jquery||e.each(["Width","Height"],function(i,a){function s(t,i,a,s){return e.each(n,function(){i-=parseFloat(e.css(t,"padding"+this))||0,a&&(i-=parseFloat(e.css(t,"border"+this+"Width"))||0),s&&(i-=parseFloat(e.css(t,"margin"+this))||0)}),i}var n="Width"===a?["Left","Right"]:["Top","Bottom"],r=a.toLowerCase(),o={innerWidth:e.fn.innerWidth,innerHeight:e.fn.innerHeight,outerWidth:e.fn.outerWidth,outerHeight:e.fn.outerHeight};e.fn["inner"+a]=function(i){return i===t?o["inner"+a].call(this):this.each(function(){e(this).css(r,s(this,i)+"px")})},e.fn["outer"+a]=function(t,i){return"number"!=typeof t?o["outer"+a].call(this,t):this.each(function(){e(this).css(r,s(this,t,!0,i)+"px")})}}),e.fn.addBack||(e.fn.addBack=function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}),e("<a>").data("a-b","a").removeData("a-b").data("a-b")&&(e.fn.removeData=function(t){return function(i){return arguments.length?t.call(this,e.camelCase(i)):t.call(this)}}(e.fn.removeData)),e.ui.ie=!!/msie [\w.]+/.exec(navigator.userAgent.toLowerCase()),e.support.selectstart="onselectstart"in document.createElement("div"),e.fn.extend({disableSelection:function(){return this.bind((e.support.selectstart?"selectstart":"mousedown")+".ui-disableSelection",function(e){e.preventDefault()})},enableSelection:function(){return this.unbind(".ui-disableSelection")}}),e.extend(e.ui,{plugin:{add:function(t,i,a){var s,n=e.ui[t].prototype;for(s in a)n.plugins[s]=n.plugins[s]||[],n.plugins[s].push([i,a[s]])},call:function(e,t,i){var a,s=e.plugins[t];if(s&&e.element[0].parentNode&&11!==e.element[0].parentNode.nodeType)for(a=0;s.length>a;a++)e.options[s[a][0]]&&s[a][1].apply(e.element,i)}},hasScroll:function(t,i){if("hidden"===e(t).css("overflow"))return!1;var a=i&&"left"===i?"scrollLeft":"scrollTop",s=!1;return t[a]>0?!0:(t[a]=1,s=t[a]>0,t[a]=0,s)}})})(jQuery);(function(e,t){var i=0,s=Array.prototype.slice,a=e.cleanData;e.cleanData=function(t){for(var i,s=0;null!=(i=t[s]);s++)try{e(i).triggerHandler("remove")}catch(n){}a(t)},e.widget=function(i,s,a){var n,r,o,h,l={},u=i.split(".")[0];i=i.split(".")[1],n=u+"-"+i,a||(a=s,s=e.Widget),e.expr[":"][n.toLowerCase()]=function(t){return!!e.data(t,n)},e[u]=e[u]||{},r=e[u][i],o=e[u][i]=function(e,i){return this._createWidget?(arguments.length&&this._createWidget(e,i),t):new o(e,i)},e.extend(o,r,{version:a.version,_proto:e.extend({},a),_childConstructors:[]}),h=new s,h.options=e.widget.extend({},h.options),e.each(a,function(i,a){return e.isFunction(a)?(l[i]=function(){var e=function(){return s.prototype[i].apply(this,arguments)},t=function(e){return s.prototype[i].apply(this,e)};return function(){var i,s=this._super,n=this._superApply;return this._super=e,this._superApply=t,i=a.apply(this,arguments),this._super=s,this._superApply=n,i}}(),t):(l[i]=a,t)}),o.prototype=e.widget.extend(h,{widgetEventPrefix:r?h.widgetEventPrefix:i},l,{constructor:o,namespace:u,widgetName:i,widgetFullName:n}),r?(e.each(r._childConstructors,function(t,i){var s=i.prototype;e.widget(s.namespace+"."+s.widgetName,o,i._proto)}),delete r._childConstructors):s._childConstructors.push(o),e.widget.bridge(i,o)},e.widget.extend=function(i){for(var a,n,r=s.call(arguments,1),o=0,h=r.length;h>o;o++)for(a in r[o])n=r[o][a],r[o].hasOwnProperty(a)&&n!==t&&(i[a]=e.isPlainObject(n)?e.isPlainObject(i[a])?e.widget.extend({},i[a],n):e.widget.extend({},n):n);return i},e.widget.bridge=function(i,a){var n=a.prototype.widgetFullName||i;e.fn[i]=function(r){var o="string"==typeof r,h=s.call(arguments,1),l=this;return r=!o&&h.length?e.widget.extend.apply(null,[r].concat(h)):r,o?this.each(function(){var s,a=e.data(this,n);return a?e.isFunction(a[r])&&"_"!==r.charAt(0)?(s=a[r].apply(a,h),s!==a&&s!==t?(l=s&&s.jquery?l.pushStack(s.get()):s,!1):t):e.error("no such method '"+r+"' for "+i+" widget instance"):e.error("cannot call methods on "+i+" prior to initialization; "+"attempted to call method '"+r+"'")}):this.each(function(){var t=e.data(this,n);t?t.option(r||{})._init():e.data(this,n,new a(r,this))}),l}},e.Widget=function(){},e.Widget._childConstructors=[],e.Widget.prototype={widgetName:"widget",widgetEventPrefix:"",defaultElement:"<div>",options:{disabled:!1,create:null},_createWidget:function(t,s){s=e(s||this.defaultElement||this)[0],this.element=e(s),this.uuid=i++,this.eventNamespace="."+this.widgetName+this.uuid,this.options=e.widget.extend({},this.options,this._getCreateOptions(),t),this.bindings=e(),this.hoverable=e(),this.focusable=e(),s!==this&&(e.data(s,this.widgetFullName,this),this._on(!0,this.element,{remove:function(e){e.target===s&&this.destroy()}}),this.document=e(s.style?s.ownerDocument:s.document||s),this.window=e(this.document[0].defaultView||this.document[0].parentWindow)),this._create(),this._trigger("create",null,this._getCreateEventData()),this._init()},_getCreateOptions:e.noop,_getCreateEventData:e.noop,_create:e.noop,_init:e.noop,destroy:function(){this._destroy(),this.element.unbind(this.eventNamespace).removeData(this.widgetName).removeData(this.widgetFullName).removeData(e.camelCase(this.widgetFullName)),this.widget().unbind(this.eventNamespace).removeAttr("aria-disabled").removeClass(this.widgetFullName+"-disabled "+"ui-state-disabled"),this.bindings.unbind(this.eventNamespace),this.hoverable.removeClass("ui-state-hover"),this.focusable.removeClass("ui-state-focus")},_destroy:e.noop,widget:function(){return this.element},option:function(i,s){var a,n,r,o=i;if(0===arguments.length)return e.widget.extend({},this.options);if("string"==typeof i)if(o={},a=i.split("."),i=a.shift(),a.length){for(n=o[i]=e.widget.extend({},this.options[i]),r=0;a.length-1>r;r++)n[a[r]]=n[a[r]]||{},n=n[a[r]];if(i=a.pop(),s===t)return n[i]===t?null:n[i];n[i]=s}else{if(s===t)return this.options[i]===t?null:this.options[i];o[i]=s}return this._setOptions(o),this},_setOptions:function(e){var t;for(t in e)this._setOption(t,e[t]);return this},_setOption:function(e,t){return this.options[e]=t,"disabled"===e&&(this.widget().toggleClass(this.widgetFullName+"-disabled ui-state-disabled",!!t).attr("aria-disabled",t),this.hoverable.removeClass("ui-state-hover"),this.focusable.removeClass("ui-state-focus")),this},enable:function(){return this._setOption("disabled",!1)},disable:function(){return this._setOption("disabled",!0)},_on:function(i,s,a){var n,r=this;"boolean"!=typeof i&&(a=s,s=i,i=!1),a?(s=n=e(s),this.bindings=this.bindings.add(s)):(a=s,s=this.element,n=this.widget()),e.each(a,function(a,o){function h(){return i||r.options.disabled!==!0&&!e(this).hasClass("ui-state-disabled")?("string"==typeof o?r[o]:o).apply(r,arguments):t}"string"!=typeof o&&(h.guid=o.guid=o.guid||h.guid||e.guid++);var l=a.match(/^(\w+)\s*(.*)$/),u=l[1]+r.eventNamespace,c=l[2];c?n.delegate(c,u,h):s.bind(u,h)})},_off:function(e,t){t=(t||"").split(" ").join(this.eventNamespace+" ")+this.eventNamespace,e.unbind(t).undelegate(t)},_delay:function(e,t){function i(){return("string"==typeof e?s[e]:e).apply(s,arguments)}var s=this;return setTimeout(i,t||0)},_hoverable:function(t){this.hoverable=this.hoverable.add(t),this._on(t,{mouseenter:function(t){e(t.currentTarget).addClass("ui-state-hover")},mouseleave:function(t){e(t.currentTarget).removeClass("ui-state-hover")}})},_focusable:function(t){this.focusable=this.focusable.add(t),this._on(t,{focusin:function(t){e(t.currentTarget).addClass("ui-state-focus")},focusout:function(t){e(t.currentTarget).removeClass("ui-state-focus")}})},_trigger:function(t,i,s){var a,n,r=this.options[t];if(s=s||{},i=e.Event(i),i.type=(t===this.widgetEventPrefix?t:this.widgetEventPrefix+t).toLowerCase(),i.target=this.element[0],n=i.originalEvent)for(a in n)a in i||(i[a]=n[a]);return this.element.trigger(i,s),!(e.isFunction(r)&&r.apply(this.element[0],[i].concat(s))===!1||i.isDefaultPrevented())}},e.each({show:"fadeIn",hide:"fadeOut"},function(t,i){e.Widget.prototype["_"+t]=function(s,a,n){"string"==typeof a&&(a={effect:a});var r,o=a?a===!0||"number"==typeof a?i:a.effect||i:t;a=a||{},"number"==typeof a&&(a={duration:a}),r=!e.isEmptyObject(a),a.complete=n,a.delay&&s.delay(a.delay),r&&e.effects&&e.effects.effect[o]?s[t](a):o!==t&&s[o]?s[o](a.duration,a.easing,n):s.queue(function(i){e(this)[t](),n&&n.call(s[0]),i()})}})})(jQuery);(function(e){var t=!1;e(document).mouseup(function(){t=!1}),e.widget("ui.mouse",{version:"1.10.3",options:{cancel:"input,textarea,button,select,option",distance:1,delay:0},_mouseInit:function(){var t=this;this.element.bind("mousedown."+this.widgetName,function(e){return t._mouseDown(e)}).bind("click."+this.widgetName,function(i){return!0===e.data(i.target,t.widgetName+".preventClickEvent")?(e.removeData(i.target,t.widgetName+".preventClickEvent"),i.stopImmediatePropagation(),!1):undefined}),this.started=!1},_mouseDestroy:function(){this.element.unbind("."+this.widgetName),this._mouseMoveDelegate&&e(document).unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate)},_mouseDown:function(i){if(!t){this._mouseStarted&&this._mouseUp(i),this._mouseDownEvent=i;var s=this,a=1===i.which,n="string"==typeof this.options.cancel&&i.target.nodeName?e(i.target).closest(this.options.cancel).length:!1;return a&&!n&&this._mouseCapture(i)?(this.mouseDelayMet=!this.options.delay,this.mouseDelayMet||(this._mouseDelayTimer=setTimeout(function(){s.mouseDelayMet=!0},this.options.delay)),this._mouseDistanceMet(i)&&this._mouseDelayMet(i)&&(this._mouseStarted=this._mouseStart(i)!==!1,!this._mouseStarted)?(i.preventDefault(),!0):(!0===e.data(i.target,this.widgetName+".preventClickEvent")&&e.removeData(i.target,this.widgetName+".preventClickEvent"),this._mouseMoveDelegate=function(e){return s._mouseMove(e)},this._mouseUpDelegate=function(e){return s._mouseUp(e)},e(document).bind("mousemove."+this.widgetName,this._mouseMoveDelegate).bind("mouseup."+this.widgetName,this._mouseUpDelegate),i.preventDefault(),t=!0,!0)):!0}},_mouseMove:function(t){return e.ui.ie&&(!document.documentMode||9>document.documentMode)&&!t.button?this._mouseUp(t):this._mouseStarted?(this._mouseDrag(t),t.preventDefault()):(this._mouseDistanceMet(t)&&this._mouseDelayMet(t)&&(this._mouseStarted=this._mouseStart(this._mouseDownEvent,t)!==!1,this._mouseStarted?this._mouseDrag(t):this._mouseUp(t)),!this._mouseStarted)},_mouseUp:function(t){return e(document).unbind("mousemove."+this.widgetName,this._mouseMoveDelegate).unbind("mouseup."+this.widgetName,this._mouseUpDelegate),this._mouseStarted&&(this._mouseStarted=!1,t.target===this._mouseDownEvent.target&&e.data(t.target,this.widgetName+".preventClickEvent",!0),this._mouseStop(t)),!1},_mouseDistanceMet:function(e){return Math.max(Math.abs(this._mouseDownEvent.pageX-e.pageX),Math.abs(this._mouseDownEvent.pageY-e.pageY))>=this.options.distance},_mouseDelayMet:function(){return this.mouseDelayMet},_mouseStart:function(){},_mouseDrag:function(){},_mouseStop:function(){},_mouseCapture:function(){return!0}})})(jQuery);(function(e,t){function i(e,t,i){return[parseFloat(e[0])*(p.test(e[0])?t/100:1),parseFloat(e[1])*(p.test(e[1])?i/100:1)]}function s(t,i){return parseInt(e.css(t,i),10)||0}function a(t){var i=t[0];return 9===i.nodeType?{width:t.width(),height:t.height(),offset:{top:0,left:0}}:e.isWindow(i)?{width:t.width(),height:t.height(),offset:{top:t.scrollTop(),left:t.scrollLeft()}}:i.preventDefault?{width:0,height:0,offset:{top:i.pageY,left:i.pageX}}:{width:t.outerWidth(),height:t.outerHeight(),offset:t.offset()}}e.ui=e.ui||{};var n,r=Math.max,o=Math.abs,h=Math.round,l=/left|center|right/,u=/top|center|bottom/,c=/[\+\-]\d+(\.[\d]+)?%?/,d=/^\w+/,p=/%$/,f=e.fn.position;e.position={scrollbarWidth:function(){if(n!==t)return n;var i,s,a=e("<div style='display:block;width:50px;height:50px;overflow:hidden;'><div style='height:100px;width:auto;'></div></div>"),r=a.children()[0];return e("body").append(a),i=r.offsetWidth,a.css("overflow","scroll"),s=r.offsetWidth,i===s&&(s=a[0].clientWidth),a.remove(),n=i-s},getScrollInfo:function(t){var i=t.isWindow?"":t.element.css("overflow-x"),s=t.isWindow?"":t.element.css("overflow-y"),a="scroll"===i||"auto"===i&&t.width<t.element[0].scrollWidth,n="scroll"===s||"auto"===s&&t.height<t.element[0].scrollHeight;return{width:n?e.position.scrollbarWidth():0,height:a?e.position.scrollbarWidth():0}},getWithinInfo:function(t){var i=e(t||window),s=e.isWindow(i[0]);return{element:i,isWindow:s,offset:i.offset()||{left:0,top:0},scrollLeft:i.scrollLeft(),scrollTop:i.scrollTop(),width:s?i.width():i.outerWidth(),height:s?i.height():i.outerHeight()}}},e.fn.position=function(t){if(!t||!t.of)return f.apply(this,arguments);t=e.extend({},t);var n,p,m,g,v,y,b=e(t.of),_=e.position.getWithinInfo(t.within),x=e.position.getScrollInfo(_),k=(t.collision||"flip").split(" "),w={};return y=a(b),b[0].preventDefault&&(t.at="left top"),p=y.width,m=y.height,g=y.offset,v=e.extend({},g),e.each(["my","at"],function(){var e,i,s=(t[this]||"").split(" ");1===s.length&&(s=l.test(s[0])?s.concat(["center"]):u.test(s[0])?["center"].concat(s):["center","center"]),s[0]=l.test(s[0])?s[0]:"center",s[1]=u.test(s[1])?s[1]:"center",e=c.exec(s[0]),i=c.exec(s[1]),w[this]=[e?e[0]:0,i?i[0]:0],t[this]=[d.exec(s[0])[0],d.exec(s[1])[0]]}),1===k.length&&(k[1]=k[0]),"right"===t.at[0]?v.left+=p:"center"===t.at[0]&&(v.left+=p/2),"bottom"===t.at[1]?v.top+=m:"center"===t.at[1]&&(v.top+=m/2),n=i(w.at,p,m),v.left+=n[0],v.top+=n[1],this.each(function(){var a,l,u=e(this),c=u.outerWidth(),d=u.outerHeight(),f=s(this,"marginLeft"),y=s(this,"marginTop"),D=c+f+s(this,"marginRight")+x.width,T=d+y+s(this,"marginBottom")+x.height,M=e.extend({},v),S=i(w.my,u.outerWidth(),u.outerHeight());"right"===t.my[0]?M.left-=c:"center"===t.my[0]&&(M.left-=c/2),"bottom"===t.my[1]?M.top-=d:"center"===t.my[1]&&(M.top-=d/2),M.left+=S[0],M.top+=S[1],e.support.offsetFractions||(M.left=h(M.left),M.top=h(M.top)),a={marginLeft:f,marginTop:y},e.each(["left","top"],function(i,s){e.ui.position[k[i]]&&e.ui.position[k[i]][s](M,{targetWidth:p,targetHeight:m,elemWidth:c,elemHeight:d,collisionPosition:a,collisionWidth:D,collisionHeight:T,offset:[n[0]+S[0],n[1]+S[1]],my:t.my,at:t.at,within:_,elem:u})}),t.using&&(l=function(e){var i=g.left-M.left,s=i+p-c,a=g.top-M.top,n=a+m-d,h={target:{element:b,left:g.left,top:g.top,width:p,height:m},element:{element:u,left:M.left,top:M.top,width:c,height:d},horizontal:0>s?"left":i>0?"right":"center",vertical:0>n?"top":a>0?"bottom":"middle"};c>p&&p>o(i+s)&&(h.horizontal="center"),d>m&&m>o(a+n)&&(h.vertical="middle"),h.important=r(o(i),o(s))>r(o(a),o(n))?"horizontal":"vertical",t.using.call(this,e,h)}),u.offset(e.extend(M,{using:l}))})},e.ui.position={fit:{left:function(e,t){var i,s=t.within,a=s.isWindow?s.scrollLeft:s.offset.left,n=s.width,o=e.left-t.collisionPosition.marginLeft,h=a-o,l=o+t.collisionWidth-n-a;t.collisionWidth>n?h>0&&0>=l?(i=e.left+h+t.collisionWidth-n-a,e.left+=h-i):e.left=l>0&&0>=h?a:h>l?a+n-t.collisionWidth:a:h>0?e.left+=h:l>0?e.left-=l:e.left=r(e.left-o,e.left)},top:function(e,t){var i,s=t.within,a=s.isWindow?s.scrollTop:s.offset.top,n=t.within.height,o=e.top-t.collisionPosition.marginTop,h=a-o,l=o+t.collisionHeight-n-a;t.collisionHeight>n?h>0&&0>=l?(i=e.top+h+t.collisionHeight-n-a,e.top+=h-i):e.top=l>0&&0>=h?a:h>l?a+n-t.collisionHeight:a:h>0?e.top+=h:l>0?e.top-=l:e.top=r(e.top-o,e.top)}},flip:{left:function(e,t){var i,s,a=t.within,n=a.offset.left+a.scrollLeft,r=a.width,h=a.isWindow?a.scrollLeft:a.offset.left,l=e.left-t.collisionPosition.marginLeft,u=l-h,c=l+t.collisionWidth-r-h,d="left"===t.my[0]?-t.elemWidth:"right"===t.my[0]?t.elemWidth:0,p="left"===t.at[0]?t.targetWidth:"right"===t.at[0]?-t.targetWidth:0,f=-2*t.offset[0];0>u?(i=e.left+d+p+f+t.collisionWidth-r-n,(0>i||o(u)>i)&&(e.left+=d+p+f)):c>0&&(s=e.left-t.collisionPosition.marginLeft+d+p+f-h,(s>0||c>o(s))&&(e.left+=d+p+f))},top:function(e,t){var i,s,a=t.within,n=a.offset.top+a.scrollTop,r=a.height,h=a.isWindow?a.scrollTop:a.offset.top,l=e.top-t.collisionPosition.marginTop,u=l-h,c=l+t.collisionHeight-r-h,d="top"===t.my[1],p=d?-t.elemHeight:"bottom"===t.my[1]?t.elemHeight:0,f="top"===t.at[1]?t.targetHeight:"bottom"===t.at[1]?-t.targetHeight:0,m=-2*t.offset[1];0>u?(s=e.top+p+f+m+t.collisionHeight-r-n,e.top+p+f+m>u&&(0>s||o(u)>s)&&(e.top+=p+f+m)):c>0&&(i=e.top-t.collisionPosition.marginTop+p+f+m-h,e.top+p+f+m>c&&(i>0||c>o(i))&&(e.top+=p+f+m))}},flipfit:{left:function(){e.ui.position.flip.left.apply(this,arguments),e.ui.position.fit.left.apply(this,arguments)},top:function(){e.ui.position.flip.top.apply(this,arguments),e.ui.position.fit.top.apply(this,arguments)}}},function(){var t,i,s,a,n,r=document.getElementsByTagName("body")[0],o=document.createElement("div");t=document.createElement(r?"div":"body"),s={visibility:"hidden",width:0,height:0,border:0,margin:0,background:"none"},r&&e.extend(s,{position:"absolute",left:"-1000px",top:"-1000px"});for(n in s)t.style[n]=s[n];t.appendChild(o),i=r||document.documentElement,i.insertBefore(t,i.firstChild),o.style.cssText="position: absolute; left: 10.7432222px;",a=e(o).offset().left,e.support.offsetFractions=a>10&&11>a,t.innerHTML="",i.removeChild(t)}()})(jQuery);(function(e){e.widget("ui.draggable",e.ui.mouse,{version:"1.10.3",widgetEventPrefix:"drag",options:{addClasses:!0,appendTo:"parent",axis:!1,connectToSortable:!1,containment:!1,cursor:"auto",cursorAt:!1,grid:!1,handle:!1,helper:"original",iframeFix:!1,opacity:!1,refreshPositions:!1,revert:!1,revertDuration:500,scope:"default",scroll:!0,scrollSensitivity:20,scrollSpeed:20,snap:!1,snapMode:"both",snapTolerance:20,stack:!1,zIndex:!1,drag:null,start:null,stop:null},_create:function(){"original"!==this.options.helper||/^(?:r|a|f)/.test(this.element.css("position"))||(this.element[0].style.position="relative"),this.options.addClasses&&this.element.addClass("ui-draggable"),this.options.disabled&&this.element.addClass("ui-draggable-disabled"),this._mouseInit()},_destroy:function(){this.element.removeClass("ui-draggable ui-draggable-dragging ui-draggable-disabled"),this._mouseDestroy()},_mouseCapture:function(t){var i=this.options;return this.helper||i.disabled||e(t.target).closest(".ui-resizable-handle").length>0?!1:(this.handle=this._getHandle(t),this.handle?(e(i.iframeFix===!0?"iframe":i.iframeFix).each(function(){e("<div class='ui-draggable-iframeFix' style='background: #fff;'></div>").css({width:this.offsetWidth+"px",height:this.offsetHeight+"px",position:"absolute",opacity:"0.001",zIndex:1e3}).css(e(this).offset()).appendTo("body")}),!0):!1)},_mouseStart:function(t){var i=this.options;return this.helper=this._createHelper(t),this.helper.addClass("ui-draggable-dragging"),this._cacheHelperProportions(),e.ui.ddmanager&&(e.ui.ddmanager.current=this),this._cacheMargins(),this.cssPosition=this.helper.css("position"),this.scrollParent=this.helper.scrollParent(),this.offsetParent=this.helper.offsetParent(),this.offsetParentCssPosition=this.offsetParent.css("position"),this.offset=this.positionAbs=this.element.offset(),this.offset={top:this.offset.top-this.margins.top,left:this.offset.left-this.margins.left},this.offset.scroll=!1,e.extend(this.offset,{click:{left:t.pageX-this.offset.left,top:t.pageY-this.offset.top},parent:this._getParentOffset(),relative:this._getRelativeOffset()}),this.originalPosition=this.position=this._generatePosition(t),this.originalPageX=t.pageX,this.originalPageY=t.pageY,i.cursorAt&&this._adjustOffsetFromHelper(i.cursorAt),this._setContainment(),this._trigger("start",t)===!1?(this._clear(),!1):(this._cacheHelperProportions(),e.ui.ddmanager&&!i.dropBehaviour&&e.ui.ddmanager.prepareOffsets(this,t),this._mouseDrag(t,!0),e.ui.ddmanager&&e.ui.ddmanager.dragStart(this,t),!0)},_mouseDrag:function(t,i){if("fixed"===this.offsetParentCssPosition&&(this.offset.parent=this._getParentOffset()),this.position=this._generatePosition(t),this.positionAbs=this._convertPositionTo("absolute"),!i){var a=this._uiHash();if(this._trigger("drag",t,a)===!1)return this._mouseUp({}),!1;this.position=a.position}return this.options.axis&&"y"===this.options.axis||(this.helper[0].style.left=this.position.left+"px"),this.options.axis&&"x"===this.options.axis||(this.helper[0].style.top=this.position.top+"px"),e.ui.ddmanager&&e.ui.ddmanager.drag(this,t),!1},_mouseStop:function(t){var i=this,a=!1;return e.ui.ddmanager&&!this.options.dropBehaviour&&(a=e.ui.ddmanager.drop(this,t)),this.dropped&&(a=this.dropped,this.dropped=!1),"original"!==this.options.helper||e.contains(this.element[0].ownerDocument,this.element[0])?("invalid"===this.options.revert&&!a||"valid"===this.options.revert&&a||this.options.revert===!0||e.isFunction(this.options.revert)&&this.options.revert.call(this.element,a)?e(this.helper).animate(this.originalPosition,parseInt(this.options.revertDuration,10),function(){i._trigger("stop",t)!==!1&&i._clear()}):this._trigger("stop",t)!==!1&&this._clear(),!1):!1},_mouseUp:function(t){return e("div.ui-draggable-iframeFix").each(function(){this.parentNode.removeChild(this)}),e.ui.ddmanager&&e.ui.ddmanager.dragStop(this,t),e.ui.mouse.prototype._mouseUp.call(this,t)},cancel:function(){return this.helper.is(".ui-draggable-dragging")?this._mouseUp({}):this._clear(),this},_getHandle:function(t){return this.options.handle?!!e(t.target).closest(this.element.find(this.options.handle)).length:!0},_createHelper:function(t){var i=this.options,a=e.isFunction(i.helper)?e(i.helper.apply(this.element[0],[t])):"clone"===i.helper?this.element.clone().removeAttr("id"):this.element;return a.parents("body").length||a.appendTo("parent"===i.appendTo?this.element[0].parentNode:i.appendTo),a[0]===this.element[0]||/(fixed|absolute)/.test(a.css("position"))||a.css("position","absolute"),a},_adjustOffsetFromHelper:function(t){"string"==typeof t&&(t=t.split(" ")),e.isArray(t)&&(t={left:+t[0],top:+t[1]||0}),"left"in t&&(this.offset.click.left=t.left+this.margins.left),"right"in t&&(this.offset.click.left=this.helperProportions.width-t.right+this.margins.left),"top"in t&&(this.offset.click.top=t.top+this.margins.top),"bottom"in t&&(this.offset.click.top=this.helperProportions.height-t.bottom+this.margins.top)},_getParentOffset:function(){var t=this.offsetParent.offset();return"absolute"===this.cssPosition&&this.scrollParent[0]!==document&&e.contains(this.scrollParent[0],this.offsetParent[0])&&(t.left+=this.scrollParent.scrollLeft(),t.top+=this.scrollParent.scrollTop()),(this.offsetParent[0]===document.body||this.offsetParent[0].tagName&&"html"===this.offsetParent[0].tagName.toLowerCase()&&e.ui.ie)&&(t={top:0,left:0}),{top:t.top+(parseInt(this.offsetParent.css("borderTopWidth"),10)||0),left:t.left+(parseInt(this.offsetParent.css("borderLeftWidth"),10)||0)}},_getRelativeOffset:function(){if("relative"===this.cssPosition){var e=this.element.position();return{top:e.top-(parseInt(this.helper.css("top"),10)||0)+this.scrollParent.scrollTop(),left:e.left-(parseInt(this.helper.css("left"),10)||0)+this.scrollParent.scrollLeft()}}return{top:0,left:0}},_cacheMargins:function(){this.margins={left:parseInt(this.element.css("marginLeft"),10)||0,top:parseInt(this.element.css("marginTop"),10)||0,right:parseInt(this.element.css("marginRight"),10)||0,bottom:parseInt(this.element.css("marginBottom"),10)||0}},_cacheHelperProportions:function(){this.helperProportions={width:this.helper.outerWidth(),height:this.helper.outerHeight()}},_setContainment:function(){var t,i,a,s=this.options;return s.containment?"window"===s.containment?(this.containment=[e(window).scrollLeft()-this.offset.relative.left-this.offset.parent.left,e(window).scrollTop()-this.offset.relative.top-this.offset.parent.top,e(window).scrollLeft()+e(window).width()-this.helperProportions.width-this.margins.left,e(window).scrollTop()+(e(window).height()||document.body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top],undefined):"document"===s.containment?(this.containment=[0,0,e(document).width()-this.helperProportions.width-this.margins.left,(e(document).height()||document.body.parentNode.scrollHeight)-this.helperProportions.height-this.margins.top],undefined):s.containment.constructor===Array?(this.containment=s.containment,undefined):("parent"===s.containment&&(s.containment=this.helper[0].parentNode),i=e(s.containment),a=i[0],a&&(t="hidden"!==i.css("overflow"),this.containment=[(parseInt(i.css("borderLeftWidth"),10)||0)+(parseInt(i.css("paddingLeft"),10)||0),(parseInt(i.css("borderTopWidth"),10)||0)+(parseInt(i.css("paddingTop"),10)||0),(t?Math.max(a.scrollWidth,a.offsetWidth):a.offsetWidth)-(parseInt(i.css("borderRightWidth"),10)||0)-(parseInt(i.css("paddingRight"),10)||0)-this.helperProportions.width-this.margins.left-this.margins.right,(t?Math.max(a.scrollHeight,a.offsetHeight):a.offsetHeight)-(parseInt(i.css("borderBottomWidth"),10)||0)-(parseInt(i.css("paddingBottom"),10)||0)-this.helperProportions.height-this.margins.top-this.margins.bottom],this.relative_container=i),undefined):(this.containment=null,undefined)},_convertPositionTo:function(t,i){i||(i=this.position);
				var a="absolute"===t?1:-1,s="absolute"!==this.cssPosition||this.scrollParent[0]!==document&&e.contains(this.scrollParent[0],this.offsetParent[0])?this.scrollParent:this.offsetParent;return this.offset.scroll||(this.offset.scroll={top:s.scrollTop(),left:s.scrollLeft()}),{top:i.top+this.offset.relative.top*a+this.offset.parent.top*a-("fixed"===this.cssPosition?-this.scrollParent.scrollTop():this.offset.scroll.top)*a,left:i.left+this.offset.relative.left*a+this.offset.parent.left*a-("fixed"===this.cssPosition?-this.scrollParent.scrollLeft():this.offset.scroll.left)*a}},_generatePosition:function(t){var i,a,s,n,r=this.options,o="absolute"!==this.cssPosition||this.scrollParent[0]!==document&&e.contains(this.scrollParent[0],this.offsetParent[0])?this.scrollParent:this.offsetParent,l=t.pageX,h=t.pageY;return this.offset.scroll||(this.offset.scroll={top:o.scrollTop(),left:o.scrollLeft()}),this.originalPosition&&(this.containment&&(this.relative_container?(a=this.relative_container.offset(),i=[this.containment[0]+a.left,this.containment[1]+a.top,this.containment[2]+a.left,this.containment[3]+a.top]):i=this.containment,t.pageX-this.offset.click.left<i[0]&&(l=i[0]+this.offset.click.left),t.pageY-this.offset.click.top<i[1]&&(h=i[1]+this.offset.click.top),t.pageX-this.offset.click.left>i[2]&&(l=i[2]+this.offset.click.left),t.pageY-this.offset.click.top>i[3]&&(h=i[3]+this.offset.click.top)),r.grid&&(s=r.grid[1]?this.originalPageY+Math.round((h-this.originalPageY)/r.grid[1])*r.grid[1]:this.originalPageY,h=i?s-this.offset.click.top>=i[1]||s-this.offset.click.top>i[3]?s:s-this.offset.click.top>=i[1]?s-r.grid[1]:s+r.grid[1]:s,n=r.grid[0]?this.originalPageX+Math.round((l-this.originalPageX)/r.grid[0])*r.grid[0]:this.originalPageX,l=i?n-this.offset.click.left>=i[0]||n-this.offset.click.left>i[2]?n:n-this.offset.click.left>=i[0]?n-r.grid[0]:n+r.grid[0]:n)),{top:h-this.offset.click.top-this.offset.relative.top-this.offset.parent.top+("fixed"===this.cssPosition?-this.scrollParent.scrollTop():this.offset.scroll.top),left:l-this.offset.click.left-this.offset.relative.left-this.offset.parent.left+("fixed"===this.cssPosition?-this.scrollParent.scrollLeft():this.offset.scroll.left)}},_clear:function(){this.helper.removeClass("ui-draggable-dragging"),this.helper[0]===this.element[0]||this.cancelHelperRemoval||this.helper.remove(),this.helper=null,this.cancelHelperRemoval=!1},_trigger:function(t,i,a){return a=a||this._uiHash(),e.ui.plugin.call(this,t,[i,a]),"drag"===t&&(this.positionAbs=this._convertPositionTo("absolute")),e.Widget.prototype._trigger.call(this,t,i,a)},plugins:{},_uiHash:function(){return{helper:this.helper,position:this.position,originalPosition:this.originalPosition,offset:this.positionAbs}}}),e.ui.plugin.add("draggable","connectToSortable",{start:function(t,i){var a=e(this).data("ui-draggable"),s=a.options,n=e.extend({},i,{item:a.element});a.sortables=[],e(s.connectToSortable).each(function(){var i=e.data(this,"ui-sortable");i&&!i.options.disabled&&(a.sortables.push({instance:i,shouldRevert:i.options.revert}),i.refreshPositions(),i._trigger("activate",t,n))})},stop:function(t,i){var a=e(this).data("ui-draggable"),s=e.extend({},i,{item:a.element});e.each(a.sortables,function(){this.instance.isOver?(this.instance.isOver=0,a.cancelHelperRemoval=!0,this.instance.cancelHelperRemoval=!1,this.shouldRevert&&(this.instance.options.revert=this.shouldRevert),this.instance._mouseStop(t),this.instance.options.helper=this.instance.options._helper,"original"===a.options.helper&&this.instance.currentItem.css({top:"auto",left:"auto"})):(this.instance.cancelHelperRemoval=!1,this.instance._trigger("deactivate",t,s))})},drag:function(t,i){var a=e(this).data("ui-draggable"),s=this;e.each(a.sortables,function(){var n=!1,r=this;this.instance.positionAbs=a.positionAbs,this.instance.helperProportions=a.helperProportions,this.instance.offset.click=a.offset.click,this.instance._intersectsWith(this.instance.containerCache)&&(n=!0,e.each(a.sortables,function(){return this.instance.positionAbs=a.positionAbs,this.instance.helperProportions=a.helperProportions,this.instance.offset.click=a.offset.click,this!==r&&this.instance._intersectsWith(this.instance.containerCache)&&e.contains(r.instance.element[0],this.instance.element[0])&&(n=!1),n})),n?(this.instance.isOver||(this.instance.isOver=1,this.instance.currentItem=e(s).clone().removeAttr("id").appendTo(this.instance.element).data("ui-sortable-item",!0),this.instance.options._helper=this.instance.options.helper,this.instance.options.helper=function(){return i.helper[0]},t.target=this.instance.currentItem[0],this.instance._mouseCapture(t,!0),this.instance._mouseStart(t,!0,!0),this.instance.offset.click.top=a.offset.click.top,this.instance.offset.click.left=a.offset.click.left,this.instance.offset.parent.left-=a.offset.parent.left-this.instance.offset.parent.left,this.instance.offset.parent.top-=a.offset.parent.top-this.instance.offset.parent.top,a._trigger("toSortable",t),a.dropped=this.instance.element,a.currentItem=a.element,this.instance.fromOutside=a),this.instance.currentItem&&this.instance._mouseDrag(t)):this.instance.isOver&&(this.instance.isOver=0,this.instance.cancelHelperRemoval=!0,this.instance.options.revert=!1,this.instance._trigger("out",t,this.instance._uiHash(this.instance)),this.instance._mouseStop(t,!0),this.instance.options.helper=this.instance.options._helper,this.instance.currentItem.remove(),this.instance.placeholder&&this.instance.placeholder.remove(),a._trigger("fromSortable",t),a.dropped=!1)})}}),e.ui.plugin.add("draggable","cursor",{start:function(){var t=e("body"),i=e(this).data("ui-draggable").options;t.css("cursor")&&(i._cursor=t.css("cursor")),t.css("cursor",i.cursor)},stop:function(){var t=e(this).data("ui-draggable").options;t._cursor&&e("body").css("cursor",t._cursor)}}),e.ui.plugin.add("draggable","opacity",{start:function(t,i){var a=e(i.helper),s=e(this).data("ui-draggable").options;a.css("opacity")&&(s._opacity=a.css("opacity")),a.css("opacity",s.opacity)},stop:function(t,i){var a=e(this).data("ui-draggable").options;a._opacity&&e(i.helper).css("opacity",a._opacity)}}),e.ui.plugin.add("draggable","scroll",{start:function(){var t=e(this).data("ui-draggable");t.scrollParent[0]!==document&&"HTML"!==t.scrollParent[0].tagName&&(t.overflowOffset=t.scrollParent.offset())},drag:function(t){var i=e(this).data("ui-draggable"),a=i.options,s=!1;i.scrollParent[0]!==document&&"HTML"!==i.scrollParent[0].tagName?(a.axis&&"x"===a.axis||(i.overflowOffset.top+i.scrollParent[0].offsetHeight-t.pageY<a.scrollSensitivity?i.scrollParent[0].scrollTop=s=i.scrollParent[0].scrollTop+a.scrollSpeed:t.pageY-i.overflowOffset.top<a.scrollSensitivity&&(i.scrollParent[0].scrollTop=s=i.scrollParent[0].scrollTop-a.scrollSpeed)),a.axis&&"y"===a.axis||(i.overflowOffset.left+i.scrollParent[0].offsetWidth-t.pageX<a.scrollSensitivity?i.scrollParent[0].scrollLeft=s=i.scrollParent[0].scrollLeft+a.scrollSpeed:t.pageX-i.overflowOffset.left<a.scrollSensitivity&&(i.scrollParent[0].scrollLeft=s=i.scrollParent[0].scrollLeft-a.scrollSpeed))):(a.axis&&"x"===a.axis||(t.pageY-e(document).scrollTop()<a.scrollSensitivity?s=e(document).scrollTop(e(document).scrollTop()-a.scrollSpeed):e(window).height()-(t.pageY-e(document).scrollTop())<a.scrollSensitivity&&(s=e(document).scrollTop(e(document).scrollTop()+a.scrollSpeed))),a.axis&&"y"===a.axis||(t.pageX-e(document).scrollLeft()<a.scrollSensitivity?s=e(document).scrollLeft(e(document).scrollLeft()-a.scrollSpeed):e(window).width()-(t.pageX-e(document).scrollLeft())<a.scrollSensitivity&&(s=e(document).scrollLeft(e(document).scrollLeft()+a.scrollSpeed)))),s!==!1&&e.ui.ddmanager&&!a.dropBehaviour&&e.ui.ddmanager.prepareOffsets(i,t)}}),e.ui.plugin.add("draggable","snap",{start:function(){var t=e(this).data("ui-draggable"),i=t.options;t.snapElements=[],e(i.snap.constructor!==String?i.snap.items||":data(ui-draggable)":i.snap).each(function(){var i=e(this),a=i.offset();this!==t.element[0]&&t.snapElements.push({item:this,width:i.outerWidth(),height:i.outerHeight(),top:a.top,left:a.left})})},drag:function(t,i){var a,s,n,r,o,l,h,u,d,c,p=e(this).data("ui-draggable"),f=p.options,m=f.snapTolerance,g=i.offset.left,v=g+p.helperProportions.width,y=i.offset.top,b=y+p.helperProportions.height;for(d=p.snapElements.length-1;d>=0;d--)o=p.snapElements[d].left,l=o+p.snapElements[d].width,h=p.snapElements[d].top,u=h+p.snapElements[d].height,o-m>v||g>l+m||h-m>b||y>u+m||!e.contains(p.snapElements[d].item.ownerDocument,p.snapElements[d].item)?(p.snapElements[d].snapping&&p.options.snap.release&&p.options.snap.release.call(p.element,t,e.extend(p._uiHash(),{snapItem:p.snapElements[d].item})),p.snapElements[d].snapping=!1):("inner"!==f.snapMode&&(a=m>=Math.abs(h-b),s=m>=Math.abs(u-y),n=m>=Math.abs(o-v),r=m>=Math.abs(l-g),a&&(i.position.top=p._convertPositionTo("relative",{top:h-p.helperProportions.height,left:0}).top-p.margins.top),s&&(i.position.top=p._convertPositionTo("relative",{top:u,left:0}).top-p.margins.top),n&&(i.position.left=p._convertPositionTo("relative",{top:0,left:o-p.helperProportions.width}).left-p.margins.left),r&&(i.position.left=p._convertPositionTo("relative",{top:0,left:l}).left-p.margins.left)),c=a||s||n||r,"outer"!==f.snapMode&&(a=m>=Math.abs(h-y),s=m>=Math.abs(u-b),n=m>=Math.abs(o-g),r=m>=Math.abs(l-v),a&&(i.position.top=p._convertPositionTo("relative",{top:h,left:0}).top-p.margins.top),s&&(i.position.top=p._convertPositionTo("relative",{top:u-p.helperProportions.height,left:0}).top-p.margins.top),n&&(i.position.left=p._convertPositionTo("relative",{top:0,left:o}).left-p.margins.left),r&&(i.position.left=p._convertPositionTo("relative",{top:0,left:l-p.helperProportions.width}).left-p.margins.left)),!p.snapElements[d].snapping&&(a||s||n||r||c)&&p.options.snap.snap&&p.options.snap.snap.call(p.element,t,e.extend(p._uiHash(),{snapItem:p.snapElements[d].item})),p.snapElements[d].snapping=a||s||n||r||c)}}),e.ui.plugin.add("draggable","stack",{start:function(){var t,i=this.data("ui-draggable").options,a=e.makeArray(e(i.stack)).sort(function(t,i){return(parseInt(e(t).css("zIndex"),10)||0)-(parseInt(e(i).css("zIndex"),10)||0)});a.length&&(t=parseInt(e(a[0]).css("zIndex"),10)||0,e(a).each(function(i){e(this).css("zIndex",t+i)}),this.css("zIndex",t+a.length))}}),e.ui.plugin.add("draggable","zIndex",{start:function(t,i){var a=e(i.helper),s=e(this).data("ui-draggable").options;a.css("zIndex")&&(s._zIndex=a.css("zIndex")),a.css("zIndex",s.zIndex)},stop:function(t,i){var a=e(this).data("ui-draggable").options;a._zIndex&&e(i.helper).css("zIndex",a._zIndex)}})})(jQuery);(function(e){function t(e){return parseInt(e,10)||0}function i(e){return!isNaN(parseInt(e,10))}e.widget("ui.resizable",e.ui.mouse,{version:"1.10.3",widgetEventPrefix:"resize",options:{alsoResize:!1,animate:!1,animateDuration:"slow",animateEasing:"swing",aspectRatio:!1,autoHide:!1,containment:!1,ghost:!1,grid:!1,handles:"e,s,se",helper:!1,maxHeight:null,maxWidth:null,minHeight:10,minWidth:10,zIndex:90,resize:null,start:null,stop:null},_create:function(){var t,i,s,a,n,r=this,o=this.options;if(this.element.addClass("ui-resizable"),e.extend(this,{_aspectRatio:!!o.aspectRatio,aspectRatio:o.aspectRatio,originalElement:this.element,_proportionallyResizeElements:[],_helper:o.helper||o.ghost||o.animate?o.helper||"ui-resizable-helper":null}),this.element[0].nodeName.match(/canvas|textarea|input|select|button|img/i)&&(this.element.wrap(e("<div class='ui-wrapper' style='overflow: hidden;'></div>").css({position:this.element.css("position"),width:this.element.outerWidth(),height:this.element.outerHeight(),top:this.element.css("top"),left:this.element.css("left")})),this.element=this.element.parent().data("ui-resizable",this.element.data("ui-resizable")),this.elementIsWrapper=!0,this.element.css({marginLeft:this.originalElement.css("marginLeft"),marginTop:this.originalElement.css("marginTop"),marginRight:this.originalElement.css("marginRight"),marginBottom:this.originalElement.css("marginBottom")}),this.originalElement.css({marginLeft:0,marginTop:0,marginRight:0,marginBottom:0}),this.originalResizeStyle=this.originalElement.css("resize"),this.originalElement.css("resize","none"),this._proportionallyResizeElements.push(this.originalElement.css({position:"static",zoom:1,display:"block"})),this.originalElement.css({margin:this.originalElement.css("margin")}),this._proportionallyResize()),this.handles=o.handles||(e(".ui-resizable-handle",this.element).length?{n:".ui-resizable-n",e:".ui-resizable-e",s:".ui-resizable-s",w:".ui-resizable-w",se:".ui-resizable-se",sw:".ui-resizable-sw",ne:".ui-resizable-ne",nw:".ui-resizable-nw"}:"e,s,se"),this.handles.constructor===String)for("all"===this.handles&&(this.handles="n,e,s,w,se,sw,ne,nw"),t=this.handles.split(","),this.handles={},i=0;t.length>i;i++)s=e.trim(t[i]),n="ui-resizable-"+s,a=e("<div class='ui-resizable-handle "+n+"'></div>"),a.css({zIndex:o.zIndex}),"se"===s&&a.addClass("ui-icon ui-icon-gripsmall-diagonal-se"),this.handles[s]=".ui-resizable-"+s,this.element.append(a);this._renderAxis=function(t){var i,s,a,n;t=t||this.element;for(i in this.handles)this.handles[i].constructor===String&&(this.handles[i]=e(this.handles[i],this.element).show()),this.elementIsWrapper&&this.originalElement[0].nodeName.match(/textarea|input|select|button/i)&&(s=e(this.handles[i],this.element),n=/sw|ne|nw|se|n|s/.test(i)?s.outerHeight():s.outerWidth(),a=["padding",/ne|nw|n/.test(i)?"Top":/se|sw|s/.test(i)?"Bottom":/^e$/.test(i)?"Right":"Left"].join(""),t.css(a,n),this._proportionallyResize()),e(this.handles[i]).length},this._renderAxis(this.element),this._handles=e(".ui-resizable-handle",this.element).disableSelection(),this._handles.mouseover(function(){r.resizing||(this.className&&(a=this.className.match(/ui-resizable-(se|sw|ne|nw|n|e|s|w)/i)),r.axis=a&&a[1]?a[1]:"se")}),o.autoHide&&(this._handles.hide(),e(this.element).addClass("ui-resizable-autohide").mouseenter(function(){o.disabled||(e(this).removeClass("ui-resizable-autohide"),r._handles.show())}).mouseleave(function(){o.disabled||r.resizing||(e(this).addClass("ui-resizable-autohide"),r._handles.hide())})),this._mouseInit()},_destroy:function(){this._mouseDestroy();var t,i=function(t){e(t).removeClass("ui-resizable ui-resizable-disabled ui-resizable-resizing").removeData("resizable").removeData("ui-resizable").unbind(".resizable").find(".ui-resizable-handle").remove()};return this.elementIsWrapper&&(i(this.element),t=this.element,this.originalElement.css({position:t.css("position"),width:t.outerWidth(),height:t.outerHeight(),top:t.css("top"),left:t.css("left")}).insertAfter(t),t.remove()),this.originalElement.css("resize",this.originalResizeStyle),i(this.originalElement),this},_mouseCapture:function(t){var i,s,a=!1;for(i in this.handles)s=e(this.handles[i])[0],(s===t.target||e.contains(s,t.target))&&(a=!0);return!this.options.disabled&&a},_mouseStart:function(i){var s,a,n,r=this.options,o=this.element.position(),h=this.element;return this.resizing=!0,/absolute/.test(h.css("position"))?h.css({position:"absolute",top:h.css("top"),left:h.css("left")}):h.is(".ui-draggable")&&h.css({position:"absolute",top:o.top,left:o.left}),this._renderProxy(),s=t(this.helper.css("left")),a=t(this.helper.css("top")),r.containment&&(s+=e(r.containment).scrollLeft()||0,a+=e(r.containment).scrollTop()||0),this.offset=this.helper.offset(),this.position={left:s,top:a},this.size=this._helper?{width:h.outerWidth(),height:h.outerHeight()}:{width:h.width(),height:h.height()},this.originalSize=this._helper?{width:h.outerWidth(),height:h.outerHeight()}:{width:h.width(),height:h.height()},this.originalPosition={left:s,top:a},this.sizeDiff={width:h.outerWidth()-h.width(),height:h.outerHeight()-h.height()},this.originalMousePosition={left:i.pageX,top:i.pageY},this.aspectRatio="number"==typeof r.aspectRatio?r.aspectRatio:this.originalSize.width/this.originalSize.height||1,n=e(".ui-resizable-"+this.axis).css("cursor"),e("body").css("cursor","auto"===n?this.axis+"-resize":n),h.addClass("ui-resizable-resizing"),this._propagate("start",i),!0},_mouseDrag:function(t){var i,s=this.helper,a={},n=this.originalMousePosition,r=this.axis,o=this.position.top,h=this.position.left,l=this.size.width,u=this.size.height,c=t.pageX-n.left||0,d=t.pageY-n.top||0,p=this._change[r];return p?(i=p.apply(this,[t,c,d]),this._updateVirtualBoundaries(t.shiftKey),(this._aspectRatio||t.shiftKey)&&(i=this._updateRatio(i,t)),i=this._respectSize(i,t),this._updateCache(i),this._propagate("resize",t),this.position.top!==o&&(a.top=this.position.top+"px"),this.position.left!==h&&(a.left=this.position.left+"px"),this.size.width!==l&&(a.width=this.size.width+"px"),this.size.height!==u&&(a.height=this.size.height+"px"),s.css(a),!this._helper&&this._proportionallyResizeElements.length&&this._proportionallyResize(),e.isEmptyObject(a)||this._trigger("resize",t,this.ui()),!1):!1},_mouseStop:function(t){this.resizing=!1;var i,s,a,n,r,o,h,l=this.options,u=this;return this._helper&&(i=this._proportionallyResizeElements,s=i.length&&/textarea/i.test(i[0].nodeName),a=s&&e.ui.hasScroll(i[0],"left")?0:u.sizeDiff.height,n=s?0:u.sizeDiff.width,r={width:u.helper.width()-n,height:u.helper.height()-a},o=parseInt(u.element.css("left"),10)+(u.position.left-u.originalPosition.left)||null,h=parseInt(u.element.css("top"),10)+(u.position.top-u.originalPosition.top)||null,l.animate||this.element.css(e.extend(r,{top:h,left:o})),u.helper.height(u.size.height),u.helper.width(u.size.width),this._helper&&!l.animate&&this._proportionallyResize()),e("body").css("cursor","auto"),this.element.removeClass("ui-resizable-resizing"),this._propagate("stop",t),this._helper&&this.helper.remove(),!1},_updateVirtualBoundaries:function(e){var t,s,a,n,r,o=this.options;r={minWidth:i(o.minWidth)?o.minWidth:0,maxWidth:i(o.maxWidth)?o.maxWidth:1/0,minHeight:i(o.minHeight)?o.minHeight:0,maxHeight:i(o.maxHeight)?o.maxHeight:1/0},(this._aspectRatio||e)&&(t=r.minHeight*this.aspectRatio,a=r.minWidth/this.aspectRatio,s=r.maxHeight*this.aspectRatio,n=r.maxWidth/this.aspectRatio,t>r.minWidth&&(r.minWidth=t),a>r.minHeight&&(r.minHeight=a),r.maxWidth>s&&(r.maxWidth=s),r.maxHeight>n&&(r.maxHeight=n)),this._vBoundaries=r},_updateCache:function(e){this.offset=this.helper.offset(),i(e.left)&&(this.position.left=e.left),i(e.top)&&(this.position.top=e.top),i(e.height)&&(this.size.height=e.height),i(e.width)&&(this.size.width=e.width)},_updateRatio:function(e){var t=this.position,s=this.size,a=this.axis;return i(e.height)?e.width=e.height*this.aspectRatio:i(e.width)&&(e.height=e.width/this.aspectRatio),"sw"===a&&(e.left=t.left+(s.width-e.width),e.top=null),"nw"===a&&(e.top=t.top+(s.height-e.height),e.left=t.left+(s.width-e.width)),e},_respectSize:function(e){var t=this._vBoundaries,s=this.axis,a=i(e.width)&&t.maxWidth&&t.maxWidth<e.width,n=i(e.height)&&t.maxHeight&&t.maxHeight<e.height,r=i(e.width)&&t.minWidth&&t.minWidth>e.width,o=i(e.height)&&t.minHeight&&t.minHeight>e.height,h=this.originalPosition.left+this.originalSize.width,l=this.position.top+this.size.height,u=/sw|nw|w/.test(s),c=/nw|ne|n/.test(s);return r&&(e.width=t.minWidth),o&&(e.height=t.minHeight),a&&(e.width=t.maxWidth),n&&(e.height=t.maxHeight),r&&u&&(e.left=h-t.minWidth),a&&u&&(e.left=h-t.maxWidth),o&&c&&(e.top=l-t.minHeight),n&&c&&(e.top=l-t.maxHeight),e.width||e.height||e.left||!e.top?e.width||e.height||e.top||!e.left||(e.left=null):e.top=null,e},_proportionallyResize:function(){if(this._proportionallyResizeElements.length){var e,t,i,s,a,n=this.helper||this.element;for(e=0;this._proportionallyResizeElements.length>e;e++){if(a=this._proportionallyResizeElements[e],!this.borderDif)for(this.borderDif=[],i=[a.css("borderTopWidth"),a.css("borderRightWidth"),a.css("borderBottomWidth"),a.css("borderLeftWidth")],s=[a.css("paddingTop"),a.css("paddingRight"),a.css("paddingBottom"),a.css("paddingLeft")],t=0;i.length>t;t++)this.borderDif[t]=(parseInt(i[t],10)||0)+(parseInt(s[t],10)||0);a.css({height:n.height()-this.borderDif[0]-this.borderDif[2]||0,width:n.width()-this.borderDif[1]-this.borderDif[3]||0})}}},_renderProxy:function(){var t=this.element,i=this.options;this.elementOffset=t.offset(),this._helper?(this.helper=this.helper||e("<div style='overflow:hidden;'></div>"),this.helper.addClass(this._helper).css({width:this.element.outerWidth()-1,height:this.element.outerHeight()-1,position:"absolute",left:this.elementOffset.left+"px",top:this.elementOffset.top+"px",zIndex:++i.zIndex}),this.helper.appendTo("body").disableSelection()):this.helper=this.element},_change:{e:function(e,t){return{width:this.originalSize.width+t}},w:function(e,t){var i=this.originalSize,s=this.originalPosition;return{left:s.left+t,width:i.width-t}},n:function(e,t,i){var s=this.originalSize,a=this.originalPosition;return{top:a.top+i,height:s.height-i}},s:function(e,t,i){return{height:this.originalSize.height+i}},se:function(t,i,s){return e.extend(this._change.s.apply(this,arguments),this._change.e.apply(this,[t,i,s]))},sw:function(t,i,s){return e.extend(this._change.s.apply(this,arguments),this._change.w.apply(this,[t,i,s]))},ne:function(t,i,s){return e.extend(this._change.n.apply(this,arguments),this._change.e.apply(this,[t,i,s]))},nw:function(t,i,s){return e.extend(this._change.n.apply(this,arguments),this._change.w.apply(this,[t,i,s]))}},_propagate:function(t,i){e.ui.plugin.call(this,t,[i,this.ui()]),"resize"!==t&&this._trigger(t,i,this.ui())},plugins:{},ui:function(){return{originalElement:this.originalElement,element:this.element,helper:this.helper,position:this.position,size:this.size,originalSize:this.originalSize,originalPosition:this.originalPosition}}}),e.ui.plugin.add("resizable","animate",{stop:function(t){var i=e(this).data("ui-resizable"),s=i.options,a=i._proportionallyResizeElements,n=a.length&&/textarea/i.test(a[0].nodeName),r=n&&e.ui.hasScroll(a[0],"left")?0:i.sizeDiff.height,o=n?0:i.sizeDiff.width,h={width:i.size.width-o,height:i.size.height-r},l=parseInt(i.element.css("left"),10)+(i.position.left-i.originalPosition.left)||null,u=parseInt(i.element.css("top"),10)+(i.position.top-i.originalPosition.top)||null;i.element.animate(e.extend(h,u&&l?{top:u,left:l}:{}),{duration:s.animateDuration,easing:s.animateEasing,step:function(){var s={width:parseInt(i.element.css("width"),10),height:parseInt(i.element.css("height"),10),top:parseInt(i.element.css("top"),10),left:parseInt(i.element.css("left"),10)};a&&a.length&&e(a[0]).css({width:s.width,height:s.height}),i._updateCache(s),i._propagate("resize",t)}})}}),e.ui.plugin.add("resizable","containment",{start:function(){var i,s,a,n,r,o,h,l=e(this).data("ui-resizable"),u=l.options,c=l.element,d=u.containment,p=d instanceof e?d.get(0):/parent/.test(d)?c.parent().get(0):d;p&&(l.containerElement=e(p),/document/.test(d)||d===document?(l.containerOffset={left:0,top:0},l.containerPosition={left:0,top:0},l.parentData={element:e(document),left:0,top:0,width:e(document).width(),height:e(document).height()||document.body.parentNode.scrollHeight}):(i=e(p),s=[],e(["Top","Right","Left","Bottom"]).each(function(e,a){s[e]=t(i.css("padding"+a))}),l.containerOffset=i.offset(),l.containerPosition=i.position(),l.containerSize={height:i.innerHeight()-s[3],width:i.innerWidth()-s[1]},a=l.containerOffset,n=l.containerSize.height,r=l.containerSize.width,o=e.ui.hasScroll(p,"left")?p.scrollWidth:r,h=e.ui.hasScroll(p)?p.scrollHeight:n,l.parentData={element:p,left:a.left,top:a.top,width:o,height:h}))},resize:function(t){var i,s,a,n,r=e(this).data("ui-resizable"),o=r.options,h=r.containerOffset,l=r.position,u=r._aspectRatio||t.shiftKey,c={top:0,left:0},d=r.containerElement;d[0]!==document&&/static/.test(d.css("position"))&&(c=h),l.left<(r._helper?h.left:0)&&(r.size.width=r.size.width+(r._helper?r.position.left-h.left:r.position.left-c.left),u&&(r.size.height=r.size.width/r.aspectRatio),r.position.left=o.helper?h.left:0),l.top<(r._helper?h.top:0)&&(r.size.height=r.size.height+(r._helper?r.position.top-h.top:r.position.top),u&&(r.size.width=r.size.height*r.aspectRatio),r.position.top=r._helper?h.top:0),r.offset.left=r.parentData.left+r.position.left,r.offset.top=r.parentData.top+r.position.top,i=Math.abs((r._helper?r.offset.left-c.left:r.offset.left-c.left)+r.sizeDiff.width),s=Math.abs((r._helper?r.offset.top-c.top:r.offset.top-h.top)+r.sizeDiff.height),a=r.containerElement.get(0)===r.element.parent().get(0),n=/relative|absolute/.test(r.containerElement.css("position")),a&&n&&(i-=r.parentData.left),i+r.size.width>=r.parentData.width&&(r.size.width=r.parentData.width-i,u&&(r.size.height=r.size.width/r.aspectRatio)),s+r.size.height>=r.parentData.height&&(r.size.height=r.parentData.height-s,u&&(r.size.width=r.size.height*r.aspectRatio))},stop:function(){var t=e(this).data("ui-resizable"),i=t.options,s=t.containerOffset,a=t.containerPosition,n=t.containerElement,r=e(t.helper),o=r.offset(),h=r.outerWidth()-t.sizeDiff.width,l=r.outerHeight()-t.sizeDiff.height;t._helper&&!i.animate&&/relative/.test(n.css("position"))&&e(this).css({left:o.left-a.left-s.left,width:h,height:l}),t._helper&&!i.animate&&/static/.test(n.css("position"))&&e(this).css({left:o.left-a.left-s.left,width:h,height:l})}}),e.ui.plugin.add("resizable","alsoResize",{start:function(){var t=e(this).data("ui-resizable"),i=t.options,s=function(t){e(t).each(function(){var t=e(this);t.data("ui-resizable-alsoresize",{width:parseInt(t.width(),10),height:parseInt(t.height(),10),left:parseInt(t.css("left"),10),top:parseInt(t.css("top"),10)})})};"object"!=typeof i.alsoResize||i.alsoResize.parentNode?s(i.alsoResize):i.alsoResize.length?(i.alsoResize=i.alsoResize[0],s(i.alsoResize)):e.each(i.alsoResize,function(e){s(e)})},resize:function(t,i){var s=e(this).data("ui-resizable"),a=s.options,n=s.originalSize,r=s.originalPosition,o={height:s.size.height-n.height||0,width:s.size.width-n.width||0,top:s.position.top-r.top||0,left:s.position.left-r.left||0},h=function(t,s){e(t).each(function(){var t=e(this),a=e(this).data("ui-resizable-alsoresize"),n={},r=s&&s.length?s:t.parents(i.originalElement[0]).length?["width","height"]:["width","height","top","left"];e.each(r,function(e,t){var i=(a[t]||0)+(o[t]||0);i&&i>=0&&(n[t]=i||null)}),t.css(n)})};"object"!=typeof a.alsoResize||a.alsoResize.nodeType?h(a.alsoResize):e.each(a.alsoResize,function(e,t){h(e,t)})},stop:function(){e(this).removeData("resizable-alsoresize")}}),e.ui.plugin.add("resizable","ghost",{start:function(){var t=e(this).data("ui-resizable"),i=t.options,s=t.size;t.ghost=t.originalElement.clone(),t.ghost.css({opacity:.25,display:"block",position:"relative",height:s.height,width:s.width,margin:0,left:0,top:0}).addClass("ui-resizable-ghost").addClass("string"==typeof i.ghost?i.ghost:""),t.ghost.appendTo(t.helper)},resize:function(){var t=e(this).data("ui-resizable");t.ghost&&t.ghost.css({position:"relative",height:t.size.height,width:t.size.width})},stop:function(){var t=e(this).data("ui-resizable");t.ghost&&t.helper&&t.helper.get(0).removeChild(t.ghost.get(0))}}),e.ui.plugin.add("resizable","grid",{resize:function(){var t=e(this).data("ui-resizable"),i=t.options,s=t.size,a=t.originalSize,n=t.originalPosition,r=t.axis,o="number"==typeof i.grid?[i.grid,i.grid]:i.grid,h=o[0]||1,l=o[1]||1,u=Math.round((s.width-a.width)/h)*h,c=Math.round((s.height-a.height)/l)*l,d=a.width+u,p=a.height+c,f=i.maxWidth&&d>i.maxWidth,m=i.maxHeight&&p>i.maxHeight,g=i.minWidth&&i.minWidth>d,v=i.minHeight&&i.minHeight>p;
				i.grid=o,g&&(d+=h),v&&(p+=l),f&&(d-=h),m&&(p-=l),/^(se|s|e)$/.test(r)?(t.size.width=d,t.size.height=p):/^(ne)$/.test(r)?(t.size.width=d,t.size.height=p,t.position.top=n.top-c):/^(sw)$/.test(r)?(t.size.width=d,t.size.height=p,t.position.left=n.left-u):(t.size.width=d,t.size.height=p,t.position.top=n.top-c,t.position.left=n.left-u)}})})(jQuery);(function(e){var t,i,a,s,n="ui-button ui-widget ui-state-default ui-corner-all",r="ui-state-hover ui-state-active ",o="ui-button-icons-only ui-button-icon-only ui-button-text-icons ui-button-text-icon-primary ui-button-text-icon-secondary ui-button-text-only",h=function(){var t=e(this);setTimeout(function(){t.find(":ui-button").button("refresh")},1)},l=function(t){var i=t.name,a=t.form,s=e([]);return i&&(i=i.replace(/'/g,"\\'"),s=a?e(a).find("[name='"+i+"']"):e("[name='"+i+"']",t.ownerDocument).filter(function(){return!this.form})),s};e.widget("ui.button",{version:"1.10.3",defaultElement:"<button>",options:{disabled:null,text:!0,label:null,icons:{primary:null,secondary:null}},_create:function(){this.element.closest("form").unbind("reset"+this.eventNamespace).bind("reset"+this.eventNamespace,h),"boolean"!=typeof this.options.disabled?this.options.disabled=!!this.element.prop("disabled"):this.element.prop("disabled",this.options.disabled),this._determineButtonType(),this.hasTitle=!!this.buttonElement.attr("title");var r=this,o=this.options,u="checkbox"===this.type||"radio"===this.type,d=u?"":"ui-state-active",c="ui-state-focus";null===o.label&&(o.label="input"===this.type?this.buttonElement.val():this.buttonElement.html()),this._hoverable(this.buttonElement),this.buttonElement.addClass(n).attr("role","button").bind("mouseenter"+this.eventNamespace,function(){o.disabled||this===t&&e(this).addClass("ui-state-active")}).bind("mouseleave"+this.eventNamespace,function(){o.disabled||e(this).removeClass(d)}).bind("click"+this.eventNamespace,function(e){o.disabled&&(e.preventDefault(),e.stopImmediatePropagation())}),this.element.bind("focus"+this.eventNamespace,function(){r.buttonElement.addClass(c)}).bind("blur"+this.eventNamespace,function(){r.buttonElement.removeClass(c)}),u&&(this.element.bind("change"+this.eventNamespace,function(){s||r.refresh()}),this.buttonElement.bind("mousedown"+this.eventNamespace,function(e){o.disabled||(s=!1,i=e.pageX,a=e.pageY)}).bind("mouseup"+this.eventNamespace,function(e){o.disabled||(i!==e.pageX||a!==e.pageY)&&(s=!0)})),"checkbox"===this.type?this.buttonElement.bind("click"+this.eventNamespace,function(){return o.disabled||s?!1:undefined}):"radio"===this.type?this.buttonElement.bind("click"+this.eventNamespace,function(){if(o.disabled||s)return!1;e(this).addClass("ui-state-active"),r.buttonElement.attr("aria-pressed","true");var t=r.element[0];l(t).not(t).map(function(){return e(this).button("widget")[0]}).removeClass("ui-state-active").attr("aria-pressed","false")}):(this.buttonElement.bind("mousedown"+this.eventNamespace,function(){return o.disabled?!1:(e(this).addClass("ui-state-active"),t=this,r.document.one("mouseup",function(){t=null}),undefined)}).bind("mouseup"+this.eventNamespace,function(){return o.disabled?!1:(e(this).removeClass("ui-state-active"),undefined)}).bind("keydown"+this.eventNamespace,function(t){return o.disabled?!1:((t.keyCode===e.ui.keyCode.SPACE||t.keyCode===e.ui.keyCode.ENTER)&&e(this).addClass("ui-state-active"),undefined)}).bind("keyup"+this.eventNamespace+" blur"+this.eventNamespace,function(){e(this).removeClass("ui-state-active")}),this.buttonElement.is("a")&&this.buttonElement.keyup(function(t){t.keyCode===e.ui.keyCode.SPACE&&e(this).click()})),this._setOption("disabled",o.disabled),this._resetButton()},_determineButtonType:function(){var e,t,i;this.type=this.element.is("[type=checkbox]")?"checkbox":this.element.is("[type=radio]")?"radio":this.element.is("input")?"input":"button","checkbox"===this.type||"radio"===this.type?(e=this.element.parents().last(),t="label[for='"+this.element.attr("id")+"']",this.buttonElement=e.find(t),this.buttonElement.length||(e=e.length?e.siblings():this.element.siblings(),this.buttonElement=e.filter(t),this.buttonElement.length||(this.buttonElement=e.find(t))),this.element.addClass("ui-helper-hidden-accessible"),i=this.element.is(":checked"),i&&this.buttonElement.addClass("ui-state-active"),this.buttonElement.prop("aria-pressed",i)):this.buttonElement=this.element},widget:function(){return this.buttonElement},_destroy:function(){this.element.removeClass("ui-helper-hidden-accessible"),this.buttonElement.removeClass(n+" "+r+" "+o).removeAttr("role").removeAttr("aria-pressed").html(this.buttonElement.find(".ui-button-text").html()),this.hasTitle||this.buttonElement.removeAttr("title")},_setOption:function(e,t){return this._super(e,t),"disabled"===e?(t?this.element.prop("disabled",!0):this.element.prop("disabled",!1),undefined):(this._resetButton(),undefined)},refresh:function(){var t=this.element.is("input, button")?this.element.is(":disabled"):this.element.hasClass("ui-button-disabled");t!==this.options.disabled&&this._setOption("disabled",t),"radio"===this.type?l(this.element[0]).each(function(){e(this).is(":checked")?e(this).button("widget").addClass("ui-state-active").attr("aria-pressed","true"):e(this).button("widget").removeClass("ui-state-active").attr("aria-pressed","false")}):"checkbox"===this.type&&(this.element.is(":checked")?this.buttonElement.addClass("ui-state-active").attr("aria-pressed","true"):this.buttonElement.removeClass("ui-state-active").attr("aria-pressed","false"))},_resetButton:function(){if("input"===this.type)return this.options.label&&this.element.val(this.options.label),undefined;var t=this.buttonElement.removeClass(o),i=e("<span></span>",this.document[0]).addClass("ui-button-text").html(this.options.label).appendTo(t.empty()).text(),a=this.options.icons,s=a.primary&&a.secondary,n=[];a.primary||a.secondary?(this.options.text&&n.push("ui-button-text-icon"+(s?"s":a.primary?"-primary":"-secondary")),a.primary&&t.prepend("<span class='ui-button-icon-primary ui-icon "+a.primary+"'></span>"),a.secondary&&t.append("<span class='ui-button-icon-secondary ui-icon "+a.secondary+"'></span>"),this.options.text||(n.push(s?"ui-button-icons-only":"ui-button-icon-only"),this.hasTitle||t.attr("title",e.trim(i)))):n.push("ui-button-text-only"),t.addClass(n.join(" "))}}),e.widget("ui.buttonset",{version:"1.10.3",options:{items:"button, input[type=button], input[type=submit], input[type=reset], input[type=checkbox], input[type=radio], a, :data(ui-button)"},_create:function(){this.element.addClass("ui-buttonset")},_init:function(){this.refresh()},_setOption:function(e,t){"disabled"===e&&this.buttons.button("option",e,t),this._super(e,t)},refresh:function(){var t="rtl"===this.element.css("direction");this.buttons=this.element.find(this.options.items).filter(":ui-button").button("refresh").end().not(":ui-button").button().end().map(function(){return e(this).button("widget")[0]}).removeClass("ui-corner-all ui-corner-left ui-corner-right").filter(":first").addClass(t?"ui-corner-right":"ui-corner-left").end().filter(":last").addClass(t?"ui-corner-left":"ui-corner-right").end().end()},_destroy:function(){this.element.removeClass("ui-buttonset"),this.buttons.map(function(){return e(this).button("widget")[0]}).removeClass("ui-corner-left ui-corner-right").end().button("destroy")}})})(jQuery);(function(e){var t={buttons:!0,height:!0,maxHeight:!0,maxWidth:!0,minHeight:!0,minWidth:!0,width:!0},i={maxHeight:!0,maxWidth:!0,minHeight:!0,minWidth:!0};e.widget("ui.dialog",{version:"1.10.3",options:{appendTo:"body",autoOpen:!0,buttons:[],closeOnEscape:!0,closeText:"close",dialogClass:"",draggable:!0,hide:null,height:"auto",maxHeight:null,maxWidth:null,minHeight:150,minWidth:150,modal:!1,position:{my:"center",at:"center",of:window,collision:"fit",using:function(t){var i=e(this).css(t).offset().top;0>i&&e(this).css("top",t.top-i)}},resizable:!0,show:null,title:null,width:300,beforeClose:null,close:null,drag:null,dragStart:null,dragStop:null,focus:null,open:null,resize:null,resizeStart:null,resizeStop:null},_create:function(){this.originalCss={display:this.element[0].style.display,width:this.element[0].style.width,minHeight:this.element[0].style.minHeight,maxHeight:this.element[0].style.maxHeight,height:this.element[0].style.height},this.originalPosition={parent:this.element.parent(),index:this.element.parent().children().index(this.element)},this.originalTitle=this.element.attr("title"),this.options.title=this.options.title||this.originalTitle,this._createWrapper(),this.element.show().removeAttr("title").addClass("ui-dialog-content ui-widget-content").appendTo(this.uiDialog),this._createTitlebar(),this._createButtonPane(),this.options.draggable&&e.fn.draggable&&this._makeDraggable(),this.options.resizable&&e.fn.resizable&&this._makeResizable(),this._isOpen=!1},_init:function(){this.options.autoOpen&&this.open()},_appendTo:function(){var t=this.options.appendTo;return t&&(t.jquery||t.nodeType)?e(t):this.document.find(t||"body").eq(0)},_destroy:function(){var e,t=this.originalPosition;this._destroyOverlay(),this.element.removeUniqueId().removeClass("ui-dialog-content ui-widget-content").css(this.originalCss).detach(),this.uiDialog.stop(!0,!0).remove(),this.originalTitle&&this.element.attr("title",this.originalTitle),e=t.parent.children().eq(t.index),e.length&&e[0]!==this.element[0]?e.before(this.element):t.parent.append(this.element)},widget:function(){return this.uiDialog},disable:e.noop,enable:e.noop,close:function(t){var i=this;this._isOpen&&this._trigger("beforeClose",t)!==!1&&(this._isOpen=!1,this._destroyOverlay(),this.opener.filter(":focusable").focus().length||e(this.document[0].activeElement).blur(),this._hide(this.uiDialog,this.options.hide,function(){i._trigger("close",t)}))},isOpen:function(){return this._isOpen},moveToTop:function(){this._moveToTop()},_moveToTop:function(e,t){var i=!!this.uiDialog.nextAll(":visible").insertBefore(this.uiDialog).length;return i&&!t&&this._trigger("focus",e),i},open:function(){var t=this;return this._isOpen?(this._moveToTop()&&this._focusTabbable(),undefined):(this._isOpen=!0,this.opener=e(this.document[0].activeElement),this._size(),this._position(),this._createOverlay(),this._moveToTop(null,!0),this._show(this.uiDialog,this.options.show,function(){t._focusTabbable(),t._trigger("focus")}),this._trigger("open"),undefined)},_focusTabbable:function(){var e=this.element.find("[autofocus]");e.length||(e=this.element.find(":tabbable")),e.length||(e=this.uiDialogButtonPane.find(":tabbable")),e.length||(e=this.uiDialogTitlebarClose.filter(":tabbable")),e.length||(e=this.uiDialog),e.eq(0).focus()},_keepFocus:function(t){function i(){var t=this.document[0].activeElement,i=this.uiDialog[0]===t||e.contains(this.uiDialog[0],t);i||this._focusTabbable()}t.preventDefault(),i.call(this),this._delay(i)},_createWrapper:function(){this.uiDialog=e("<div>").addClass("ui-dialog ui-widget ui-widget-content ui-corner-all ui-front "+this.options.dialogClass).hide().attr({tabIndex:-1,role:"dialog"}).appendTo(this._appendTo()),this._on(this.uiDialog,{keydown:function(t){if(this.options.closeOnEscape&&!t.isDefaultPrevented()&&t.keyCode&&t.keyCode===e.ui.keyCode.ESCAPE)return t.preventDefault(),this.close(t),undefined;if(t.keyCode===e.ui.keyCode.TAB){var i=this.uiDialog.find(":tabbable"),a=i.filter(":first"),s=i.filter(":last");t.target!==s[0]&&t.target!==this.uiDialog[0]||t.shiftKey?t.target!==a[0]&&t.target!==this.uiDialog[0]||!t.shiftKey||(s.focus(1),t.preventDefault()):(a.focus(1),t.preventDefault())}},mousedown:function(e){this._moveToTop(e)&&this._focusTabbable()}}),this.element.find("[aria-describedby]").length||this.uiDialog.attr({"aria-describedby":this.element.uniqueId().attr("id")})},_createTitlebar:function(){var t;this.uiDialogTitlebar=e("<div>").addClass("ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix").prependTo(this.uiDialog),this._on(this.uiDialogTitlebar,{mousedown:function(t){e(t.target).closest(".ui-dialog-titlebar-close")||this.uiDialog.focus()}}),this.uiDialogTitlebarClose=e("<button></button>").button({label:this.options.closeText,icons:{primary:"ui-icon-closethick"},text:!1}).addClass("ui-dialog-titlebar-close").appendTo(this.uiDialogTitlebar),this._on(this.uiDialogTitlebarClose,{click:function(e){e.preventDefault(),this.close(e)}}),t=e("<span>").uniqueId().addClass("ui-dialog-title").prependTo(this.uiDialogTitlebar),this._title(t),this.uiDialog.attr({"aria-labelledby":t.attr("id")})},_title:function(e){this.options.title||e.html("&#160;"),e.text(this.options.title)},_createButtonPane:function(){this.uiDialogButtonPane=e("<div>").addClass("ui-dialog-buttonpane ui-widget-content ui-helper-clearfix"),this.uiButtonSet=e("<div>").addClass("ui-dialog-buttonset").appendTo(this.uiDialogButtonPane),this._createButtons()},_createButtons:function(){var t=this,i=this.options.buttons;return this.uiDialogButtonPane.remove(),this.uiButtonSet.empty(),e.isEmptyObject(i)||e.isArray(i)&&!i.length?(this.uiDialog.removeClass("ui-dialog-buttons"),undefined):(e.each(i,function(i,a){var s,n;a=e.isFunction(a)?{click:a,text:i}:a,a=e.extend({type:"button"},a),s=a.click,a.click=function(){s.apply(t.element[0],arguments)},n={icons:a.icons,text:a.showText},delete a.icons,delete a.showText,e("<button></button>",a).button(n).appendTo(t.uiButtonSet)}),this.uiDialog.addClass("ui-dialog-buttons"),this.uiDialogButtonPane.appendTo(this.uiDialog),undefined)},_makeDraggable:function(){function t(e){return{position:e.position,offset:e.offset}}var i=this,a=this.options;this.uiDialog.draggable({cancel:".ui-dialog-content, .ui-dialog-titlebar-close",handle:".ui-dialog-titlebar",containment:"document",start:function(a,s){e(this).addClass("ui-dialog-dragging"),i._blockFrames(),i._trigger("dragStart",a,t(s))},drag:function(e,a){i._trigger("drag",e,t(a))},stop:function(s,n){a.position=[n.position.left-i.document.scrollLeft(),n.position.top-i.document.scrollTop()],e(this).removeClass("ui-dialog-dragging"),i._unblockFrames(),i._trigger("dragStop",s,t(n))}})},_makeResizable:function(){function t(e){return{originalPosition:e.originalPosition,originalSize:e.originalSize,position:e.position,size:e.size}}var i=this,a=this.options,s=a.resizable,n=this.uiDialog.css("position"),r="string"==typeof s?s:"n,e,s,w,se,sw,ne,nw";this.uiDialog.resizable({cancel:".ui-dialog-content",containment:"document",alsoResize:this.element,maxWidth:a.maxWidth,maxHeight:a.maxHeight,minWidth:a.minWidth,minHeight:this._minHeight(),handles:r,start:function(a,s){e(this).addClass("ui-dialog-resizing"),i._blockFrames(),i._trigger("resizeStart",a,t(s))},resize:function(e,a){i._trigger("resize",e,t(a))},stop:function(s,n){a.height=e(this).height(),a.width=e(this).width(),e(this).removeClass("ui-dialog-resizing"),i._unblockFrames(),i._trigger("resizeStop",s,t(n))}}).css("position",n)},_minHeight:function(){var e=this.options;return"auto"===e.height?e.minHeight:Math.min(e.minHeight,e.height)},_position:function(){var e=this.uiDialog.is(":visible");e||this.uiDialog.show(),this.uiDialog.position(this.options.position),e||this.uiDialog.hide()},_setOptions:function(a){var s=this,n=!1,r={};e.each(a,function(e,a){s._setOption(e,a),e in t&&(n=!0),e in i&&(r[e]=a)}),n&&(this._size(),this._position()),this.uiDialog.is(":data(ui-resizable)")&&this.uiDialog.resizable("option",r)},_setOption:function(e,t){var i,a,s=this.uiDialog;"dialogClass"===e&&s.removeClass(this.options.dialogClass).addClass(t),"disabled"!==e&&(this._super(e,t),"appendTo"===e&&this.uiDialog.appendTo(this._appendTo()),"buttons"===e&&this._createButtons(),"closeText"===e&&this.uiDialogTitlebarClose.button({label:""+t}),"draggable"===e&&(i=s.is(":data(ui-draggable)"),i&&!t&&s.draggable("destroy"),!i&&t&&this._makeDraggable()),"position"===e&&this._position(),"resizable"===e&&(a=s.is(":data(ui-resizable)"),a&&!t&&s.resizable("destroy"),a&&"string"==typeof t&&s.resizable("option","handles",t),a||t===!1||this._makeResizable()),"title"===e&&this._title(this.uiDialogTitlebar.find(".ui-dialog-title")))},_size:function(){var e,t,i,a=this.options;this.element.show().css({width:"auto",minHeight:0,maxHeight:"none",height:0}),a.minWidth>a.width&&(a.width=a.minWidth),e=this.uiDialog.css({height:"auto",width:a.width}).outerHeight(),t=Math.max(0,a.minHeight-e),i="number"==typeof a.maxHeight?Math.max(0,a.maxHeight-e):"none","auto"===a.height?this.element.css({minHeight:t,maxHeight:i,height:"auto"}):this.element.height(Math.max(0,a.height-e)),this.uiDialog.is(":data(ui-resizable)")&&this.uiDialog.resizable("option","minHeight",this._minHeight())},_blockFrames:function(){this.iframeBlocks=this.document.find("iframe").map(function(){var t=e(this);return e("<div>").css({position:"absolute",width:t.outerWidth(),height:t.outerHeight()}).appendTo(t.parent()).offset(t.offset())[0]})},_unblockFrames:function(){this.iframeBlocks&&(this.iframeBlocks.remove(),delete this.iframeBlocks)},_allowInteraction:function(t){return e(t.target).closest(".ui-dialog").length?!0:!!e(t.target).closest(".ui-datepicker").length},_createOverlay:function(){if(this.options.modal){var t=this,i=this.widgetFullName;e.ui.dialog.overlayInstances||this._delay(function(){e.ui.dialog.overlayInstances&&this.document.bind("focusin.dialog",function(a){t._allowInteraction(a)||(a.preventDefault(),e(".ui-dialog:visible:last .ui-dialog-content").data(i)._focusTabbable())})}),this.overlay=e("<div>").addClass("ui-widget-overlay ui-front").appendTo(this._appendTo()),this._on(this.overlay,{mousedown:"_keepFocus"}),e.ui.dialog.overlayInstances++}},_destroyOverlay:function(){this.options.modal&&this.overlay&&(e.ui.dialog.overlayInstances--,e.ui.dialog.overlayInstances||this.document.unbind("focusin.dialog"),this.overlay.remove(),this.overlay=null)}}),e.ui.dialog.overlayInstances=0,e.uiBackCompat!==!1&&e.widget("ui.dialog",e.ui.dialog,{_position:function(){var t,i=this.options.position,a=[],s=[0,0];i?(("string"==typeof i||"object"==typeof i&&"0"in i)&&(a=i.split?i.split(" "):[i[0],i[1]],1===a.length&&(a[1]=a[0]),e.each(["left","top"],function(e,t){+a[e]===a[e]&&(s[e]=a[e],a[e]=t)}),i={my:a[0]+(0>s[0]?s[0]:"+"+s[0])+" "+a[1]+(0>s[1]?s[1]:"+"+s[1]),at:a.join(" ")}),i=e.extend({},e.ui.dialog.prototype.options.position,i)):i=e.ui.dialog.prototype.options.position,t=this.uiDialog.is(":visible"),t||this.uiDialog.show(),this.uiDialog.position(i),t||this.uiDialog.hide()}})})(jQuery);(function(e,t){e.widget("ui.progressbar",{version:"1.10.3",options:{max:100,value:0,change:null,complete:null},min:0,_create:function(){this.oldValue=this.options.value=this._constrainedValue(),this.element.addClass("ui-progressbar ui-widget ui-widget-content ui-corner-all").attr({role:"progressbar","aria-valuemin":this.min}),this.valueDiv=e("<div class='ui-progressbar-value ui-widget-header ui-corner-left'></div>").appendTo(this.element),this._refreshValue()},_destroy:function(){this.element.removeClass("ui-progressbar ui-widget ui-widget-content ui-corner-all").removeAttr("role").removeAttr("aria-valuemin").removeAttr("aria-valuemax").removeAttr("aria-valuenow"),this.valueDiv.remove()},value:function(e){return e===t?this.options.value:(this.options.value=this._constrainedValue(e),this._refreshValue(),t)},_constrainedValue:function(e){return e===t&&(e=this.options.value),this.indeterminate=e===!1,"number"!=typeof e&&(e=0),this.indeterminate?!1:Math.min(this.options.max,Math.max(this.min,e))},_setOptions:function(e){var t=e.value;delete e.value,this._super(e),this.options.value=this._constrainedValue(t),this._refreshValue()},_setOption:function(e,t){"max"===e&&(t=Math.max(this.min,t)),this._super(e,t)},_percentage:function(){return this.indeterminate?100:100*(this.options.value-this.min)/(this.options.max-this.min)},_refreshValue:function(){var t=this.options.value,i=this._percentage();this.valueDiv.toggle(this.indeterminate||t>this.min).toggleClass("ui-corner-right",t===this.options.max).width(i.toFixed(0)+"%"),this.element.toggleClass("ui-progressbar-indeterminate",this.indeterminate),this.indeterminate?(this.element.removeAttr("aria-valuenow"),this.overlayDiv||(this.overlayDiv=e("<div class='ui-progressbar-overlay'></div>").appendTo(this.valueDiv))):(this.element.attr({"aria-valuemax":this.options.max,"aria-valuenow":t}),this.overlayDiv&&(this.overlayDiv.remove(),this.overlayDiv=null)),this.oldValue!==t&&(this.oldValue=t,this._trigger("change")),t===this.options.max&&this._trigger("complete")}})})(jQuery);(function(e,t){var i="ui-effects-";e.effects={effect:{}},function(e,t){function i(e,t,i){var s=c[t.type]||{};return null==e?i||!t.def?null:t.def:(e=s.floor?~~e:parseFloat(e),isNaN(e)?t.def:s.mod?(e+s.mod)%s.mod:0>e?0:e>s.max?s.max:e)}function s(i){var s=l(),a=s._rgba=[];return i=i.toLowerCase(),f(h,function(e,n){var r,o=n.re.exec(i),h=o&&n.parse(o),l=n.space||"rgba";return h?(r=s[l](h),s[u[l].cache]=r[u[l].cache],a=s._rgba=r._rgba,!1):t}),a.length?("0,0,0,0"===a.join()&&e.extend(a,n.transparent),s):n[i]}function a(e,t,i){return i=(i+1)%1,1>6*i?e+6*(t-e)*i:1>2*i?t:2>3*i?e+6*(t-e)*(2/3-i):e}var n,r="backgroundColor borderBottomColor borderLeftColor borderRightColor borderTopColor color columnRuleColor outlineColor textDecorationColor textEmphasisColor",o=/^([\-+])=\s*(\d+\.?\d*)/,h=[{re:/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(\d?(?:\.\d+)?)\s*)?\)/,parse:function(e){return[e[1],e[2],e[3],e[4]]}},{re:/rgba?\(\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*(?:,\s*(\d?(?:\.\d+)?)\s*)?\)/,parse:function(e){return[2.55*e[1],2.55*e[2],2.55*e[3],e[4]]}},{re:/#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})/,parse:function(e){return[parseInt(e[1],16),parseInt(e[2],16),parseInt(e[3],16)]}},{re:/#([a-f0-9])([a-f0-9])([a-f0-9])/,parse:function(e){return[parseInt(e[1]+e[1],16),parseInt(e[2]+e[2],16),parseInt(e[3]+e[3],16)]}},{re:/hsla?\(\s*(\d+(?:\.\d+)?)\s*,\s*(\d+(?:\.\d+)?)\%\s*,\s*(\d+(?:\.\d+)?)\%\s*(?:,\s*(\d?(?:\.\d+)?)\s*)?\)/,space:"hsla",parse:function(e){return[e[1],e[2]/100,e[3]/100,e[4]]}}],l=e.Color=function(t,i,s,a){return new e.Color.fn.parse(t,i,s,a)},u={rgba:{props:{red:{idx:0,type:"byte"},green:{idx:1,type:"byte"},blue:{idx:2,type:"byte"}}},hsla:{props:{hue:{idx:0,type:"degrees"},saturation:{idx:1,type:"percent"},lightness:{idx:2,type:"percent"}}}},c={"byte":{floor:!0,max:255},percent:{max:1},degrees:{mod:360,floor:!0}},d=l.support={},p=e("<p>")[0],f=e.each;p.style.cssText="background-color:rgba(1,1,1,.5)",d.rgba=p.style.backgroundColor.indexOf("rgba")>-1,f(u,function(e,t){t.cache="_"+e,t.props.alpha={idx:3,type:"percent",def:1}}),l.fn=e.extend(l.prototype,{parse:function(a,r,o,h){if(a===t)return this._rgba=[null,null,null,null],this;(a.jquery||a.nodeType)&&(a=e(a).css(r),r=t);var c=this,d=e.type(a),p=this._rgba=[];return r!==t&&(a=[a,r,o,h],d="array"),"string"===d?this.parse(s(a)||n._default):"array"===d?(f(u.rgba.props,function(e,t){p[t.idx]=i(a[t.idx],t)}),this):"object"===d?(a instanceof l?f(u,function(e,t){a[t.cache]&&(c[t.cache]=a[t.cache].slice())}):f(u,function(t,s){var n=s.cache;f(s.props,function(e,t){if(!c[n]&&s.to){if("alpha"===e||null==a[e])return;c[n]=s.to(c._rgba)}c[n][t.idx]=i(a[e],t,!0)}),c[n]&&0>e.inArray(null,c[n].slice(0,3))&&(c[n][3]=1,s.from&&(c._rgba=s.from(c[n])))}),this):t},is:function(e){var i=l(e),s=!0,a=this;return f(u,function(e,n){var r,o=i[n.cache];return o&&(r=a[n.cache]||n.to&&n.to(a._rgba)||[],f(n.props,function(e,i){return null!=o[i.idx]?s=o[i.idx]===r[i.idx]:t})),s}),s},_space:function(){var e=[],t=this;return f(u,function(i,s){t[s.cache]&&e.push(i)}),e.pop()},transition:function(e,t){var s=l(e),a=s._space(),n=u[a],r=0===this.alpha()?l("transparent"):this,o=r[n.cache]||n.to(r._rgba),h=o.slice();return s=s[n.cache],f(n.props,function(e,a){var n=a.idx,r=o[n],l=s[n],u=c[a.type]||{};null!==l&&(null===r?h[n]=l:(u.mod&&(l-r>u.mod/2?r+=u.mod:r-l>u.mod/2&&(r-=u.mod)),h[n]=i((l-r)*t+r,a)))}),this[a](h)},blend:function(t){if(1===this._rgba[3])return this;var i=this._rgba.slice(),s=i.pop(),a=l(t)._rgba;return l(e.map(i,function(e,t){return(1-s)*a[t]+s*e}))},toRgbaString:function(){var t="rgba(",i=e.map(this._rgba,function(e,t){return null==e?t>2?1:0:e});return 1===i[3]&&(i.pop(),t="rgb("),t+i.join()+")"},toHslaString:function(){var t="hsla(",i=e.map(this.hsla(),function(e,t){return null==e&&(e=t>2?1:0),t&&3>t&&(e=Math.round(100*e)+"%"),e});return 1===i[3]&&(i.pop(),t="hsl("),t+i.join()+")"},toHexString:function(t){var i=this._rgba.slice(),s=i.pop();return t&&i.push(~~(255*s)),"#"+e.map(i,function(e){return e=(e||0).toString(16),1===e.length?"0"+e:e}).join("")},toString:function(){return 0===this._rgba[3]?"transparent":this.toRgbaString()}}),l.fn.parse.prototype=l.fn,u.hsla.to=function(e){if(null==e[0]||null==e[1]||null==e[2])return[null,null,null,e[3]];var t,i,s=e[0]/255,a=e[1]/255,n=e[2]/255,r=e[3],o=Math.max(s,a,n),h=Math.min(s,a,n),l=o-h,u=o+h,c=.5*u;return t=h===o?0:s===o?60*(a-n)/l+360:a===o?60*(n-s)/l+120:60*(s-a)/l+240,i=0===l?0:.5>=c?l/u:l/(2-u),[Math.round(t)%360,i,c,null==r?1:r]},u.hsla.from=function(e){if(null==e[0]||null==e[1]||null==e[2])return[null,null,null,e[3]];var t=e[0]/360,i=e[1],s=e[2],n=e[3],r=.5>=s?s*(1+i):s+i-s*i,o=2*s-r;return[Math.round(255*a(o,r,t+1/3)),Math.round(255*a(o,r,t)),Math.round(255*a(o,r,t-1/3)),n]},f(u,function(s,a){var n=a.props,r=a.cache,h=a.to,u=a.from;l.fn[s]=function(s){if(h&&!this[r]&&(this[r]=h(this._rgba)),s===t)return this[r].slice();var a,o=e.type(s),c="array"===o||"object"===o?s:arguments,d=this[r].slice();return f(n,function(e,t){var s=c["object"===o?e:t.idx];null==s&&(s=d[t.idx]),d[t.idx]=i(s,t)}),u?(a=l(u(d)),a[r]=d,a):l(d)},f(n,function(t,i){l.fn[t]||(l.fn[t]=function(a){var n,r=e.type(a),h="alpha"===t?this._hsla?"hsla":"rgba":s,l=this[h](),u=l[i.idx];return"undefined"===r?u:("function"===r&&(a=a.call(this,u),r=e.type(a)),null==a&&i.empty?this:("string"===r&&(n=o.exec(a),n&&(a=u+parseFloat(n[2])*("+"===n[1]?1:-1))),l[i.idx]=a,this[h](l)))})})}),l.hook=function(t){var i=t.split(" ");f(i,function(t,i){e.cssHooks[i]={set:function(t,a){var n,r,o="";if("transparent"!==a&&("string"!==e.type(a)||(n=s(a)))){if(a=l(n||a),!d.rgba&&1!==a._rgba[3]){for(r="backgroundColor"===i?t.parentNode:t;(""===o||"transparent"===o)&&r&&r.style;)try{o=e.css(r,"backgroundColor"),r=r.parentNode}catch(h){}a=a.blend(o&&"transparent"!==o?o:"_default")}a=a.toRgbaString()}try{t.style[i]=a}catch(h){}}},e.fx.step[i]=function(t){t.colorInit||(t.start=l(t.elem,i),t.end=l(t.end),t.colorInit=!0),e.cssHooks[i].set(t.elem,t.start.transition(t.end,t.pos))}})},l.hook(r),e.cssHooks.borderColor={expand:function(e){var t={};return f(["Top","Right","Bottom","Left"],function(i,s){t["border"+s+"Color"]=e}),t}},n=e.Color.names={aqua:"#00ffff",black:"#000000",blue:"#0000ff",fuchsia:"#ff00ff",gray:"#808080",green:"#008000",lime:"#00ff00",maroon:"#800000",navy:"#000080",olive:"#808000",purple:"#800080",red:"#ff0000",silver:"#c0c0c0",teal:"#008080",white:"#ffffff",yellow:"#ffff00",transparent:[null,null,null,0],_default:"#ffffff"}}(jQuery),function(){function i(t){var i,s,a=t.ownerDocument.defaultView?t.ownerDocument.defaultView.getComputedStyle(t,null):t.currentStyle,n={};if(a&&a.length&&a[0]&&a[a[0]])for(s=a.length;s--;)i=a[s],"string"==typeof a[i]&&(n[e.camelCase(i)]=a[i]);else for(i in a)"string"==typeof a[i]&&(n[i]=a[i]);return n}function s(t,i){var s,a,r={};for(s in i)a=i[s],t[s]!==a&&(n[s]||(e.fx.step[s]||!isNaN(parseFloat(a)))&&(r[s]=a));
				return r}var a=["add","remove","toggle"],n={border:1,borderBottom:1,borderColor:1,borderLeft:1,borderRight:1,borderTop:1,borderWidth:1,margin:1,padding:1};e.each(["borderLeftStyle","borderRightStyle","borderBottomStyle","borderTopStyle"],function(t,i){e.fx.step[i]=function(e){("none"!==e.end&&!e.setAttr||1===e.pos&&!e.setAttr)&&(jQuery.style(e.elem,i,e.end),e.setAttr=!0)}}),e.fn.addBack||(e.fn.addBack=function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}),e.effects.animateClass=function(t,n,r,o){var h=e.speed(n,r,o);
				return this.queue(function(){var n,r=e(this),o=r.attr("class")||"",l=h.children?r.find("*").addBack():r;l=l.map(function(){var t=e(this);return{el:t,start:i(this)}}),n=function(){e.each(a,function(e,i){t[i]&&r[i+"Class"](t[i])})},n(),l=l.map(function(){return this.end=i(this.el[0]),this.diff=s(this.start,this.end),this}),r.attr("class",o),l=l.map(function(){var t=this,i=e.Deferred(),s=e.extend({},h,{queue:!1,complete:function(){i.resolve(t)}});return this.el.animate(this.diff,s),i.promise()}),e.when.apply(e,l.get()).done(function(){n(),e.each(arguments,function(){var t=this.el;e.each(this.diff,function(e){t.css(e,"")})}),h.complete.call(r[0])})})},e.fn.extend({addClass:function(t){return function(i,s,a,n){return s?e.effects.animateClass.call(this,{add:i},s,a,n):t.apply(this,arguments)}}(e.fn.addClass),removeClass:function(t){return function(i,s,a,n){return arguments.length>1?e.effects.animateClass.call(this,{remove:i},s,a,n):t.apply(this,arguments)}}(e.fn.removeClass),toggleClass:function(i){return function(s,a,n,r,o){return"boolean"==typeof a||a===t?n?e.effects.animateClass.call(this,a?{add:s}:{remove:s},n,r,o):i.apply(this,arguments):e.effects.animateClass.call(this,{toggle:s},a,n,r)}}(e.fn.toggleClass),switchClass:function(t,i,s,a,n){return e.effects.animateClass.call(this,{add:i,remove:t},s,a,n)}})}(),function(){function s(t,i,s,a){return e.isPlainObject(t)&&(i=t,t=t.effect),t={effect:t},null==i&&(i={}),e.isFunction(i)&&(a=i,s=null,i={}),("number"==typeof i||e.fx.speeds[i])&&(a=s,s=i,i={}),e.isFunction(s)&&(a=s,s=null),i&&e.extend(t,i),s=s||i.duration,t.duration=e.fx.off?0:"number"==typeof s?s:s in e.fx.speeds?e.fx.speeds[s]:e.fx.speeds._default,t.complete=a||i.complete,t}function a(t){return!t||"number"==typeof t||e.fx.speeds[t]?!0:"string"!=typeof t||e.effects.effect[t]?e.isFunction(t)?!0:"object"!=typeof t||t.effect?!1:!0:!0}e.extend(e.effects,{version:"1.10.3",save:function(e,t){for(var s=0;t.length>s;s++)null!==t[s]&&e.data(i+t[s],e[0].style[t[s]])},restore:function(e,s){var a,n;for(n=0;s.length>n;n++)null!==s[n]&&(a=e.data(i+s[n]),a===t&&(a=""),e.css(s[n],a))},setMode:function(e,t){return"toggle"===t&&(t=e.is(":hidden")?"show":"hide"),t},getBaseline:function(e,t){var i,s;switch(e[0]){case"top":i=0;break;case"middle":i=.5;break;case"bottom":i=1;break;default:i=e[0]/t.height}switch(e[1]){case"left":s=0;break;case"center":s=.5;break;case"right":s=1;break;default:s=e[1]/t.width}return{x:s,y:i}},createWrapper:function(t){if(t.parent().is(".ui-effects-wrapper"))return t.parent();var i={width:t.outerWidth(!0),height:t.outerHeight(!0),"float":t.css("float")},s=e("<div></div>").addClass("ui-effects-wrapper").css({fontSize:"100%",background:"transparent",border:"none",margin:0,padding:0}),a={width:t.width(),height:t.height()},n=document.activeElement;try{n.id}catch(r){n=document.body}return t.wrap(s),(t[0]===n||e.contains(t[0],n))&&e(n).focus(),s=t.parent(),"static"===t.css("position")?(s.css({position:"relative"}),t.css({position:"relative"})):(e.extend(i,{position:t.css("position"),zIndex:t.css("z-index")}),e.each(["top","left","bottom","right"],function(e,s){i[s]=t.css(s),isNaN(parseInt(i[s],10))&&(i[s]="auto")}),t.css({position:"relative",top:0,left:0,right:"auto",bottom:"auto"})),t.css(a),s.css(i).show()},removeWrapper:function(t){var i=document.activeElement;return t.parent().is(".ui-effects-wrapper")&&(t.parent().replaceWith(t),(t[0]===i||e.contains(t[0],i))&&e(i).focus()),t},setTransition:function(t,i,s,a){return a=a||{},e.each(i,function(e,i){var n=t.cssUnit(i);n[0]>0&&(a[i]=n[0]*s+n[1])}),a}}),e.fn.extend({effect:function(){function t(t){function s(){e.isFunction(n)&&n.call(a[0]),e.isFunction(t)&&t()}var a=e(this),n=i.complete,o=i.mode;(a.is(":hidden")?"hide"===o:"show"===o)?(a[o](),s()):r.call(a[0],i,s)}var i=s.apply(this,arguments),a=i.mode,n=i.queue,r=e.effects.effect[i.effect];return e.fx.off||!r?a?this[a](i.duration,i.complete):this.each(function(){i.complete&&i.complete.call(this)}):n===!1?this.each(t):this.queue(n||"fx",t)},show:function(e){return function(t){if(a(t))return e.apply(this,arguments);var i=s.apply(this,arguments);return i.mode="show",this.effect.call(this,i)}}(e.fn.show),hide:function(e){return function(t){if(a(t))return e.apply(this,arguments);var i=s.apply(this,arguments);return i.mode="hide",this.effect.call(this,i)}}(e.fn.hide),toggle:function(e){return function(t){if(a(t)||"boolean"==typeof t)return e.apply(this,arguments);var i=s.apply(this,arguments);return i.mode="toggle",this.effect.call(this,i)}}(e.fn.toggle),cssUnit:function(t){var i=this.css(t),s=[];return e.each(["em","px","%","pt"],function(e,t){i.indexOf(t)>0&&(s=[parseFloat(i),t])}),s}})}(),function(){var t={};e.each(["Quad","Cubic","Quart","Quint","Expo"],function(e,i){t[i]=function(t){return Math.pow(t,e+2)}}),e.extend(t,{Sine:function(e){return 1-Math.cos(e*Math.PI/2)},Circ:function(e){return 1-Math.sqrt(1-e*e)},Elastic:function(e){return 0===e||1===e?e:-Math.pow(2,8*(e-1))*Math.sin((80*(e-1)-7.5)*Math.PI/15)},Back:function(e){return e*e*(3*e-2)},Bounce:function(e){for(var t,i=4;((t=Math.pow(2,--i))-1)/11>e;);return 1/Math.pow(4,3-i)-7.5625*Math.pow((3*t-2)/22-e,2)}}),e.each(t,function(t,i){e.easing["easeIn"+t]=i,e.easing["easeOut"+t]=function(e){return 1-i(1-e)},e.easing["easeInOut"+t]=function(e){return.5>e?i(2*e)/2:1-i(-2*e+2)/2}})}()})(jQuery);(function(e){var t=/up|down|vertical/,i=/up|left|vertical|horizontal/;e.effects.effect.blind=function(a,s){var n,r,o,l=e(this),h=["position","top","bottom","left","right","height","width"],u=e.effects.setMode(l,a.mode||"hide"),d=a.direction||"up",c=t.test(d),p=c?"height":"width",f=c?"top":"left",m=i.test(d),g={},v="show"===u;l.parent().is(".ui-effects-wrapper")?e.effects.save(l.parent(),h):e.effects.save(l,h),l.show(),n=e.effects.createWrapper(l).css({overflow:"hidden"}),r=n[p](),o=parseFloat(n.css(f))||0,g[p]=v?r:0,m||(l.css(c?"bottom":"right",0).css(c?"top":"left","auto").css({position:"absolute"}),g[f]=v?o:r+o),v&&(n.css(p,0),m||n.css(f,o+r)),n.animate(g,{duration:a.duration,easing:a.easing,queue:!1,complete:function(){"hide"===u&&l.hide(),e.effects.restore(l,h),e.effects.removeWrapper(l),s()}})}})(jQuery);(function(e){e.effects.effect.clip=function(t,i){var a,s,n,r=e(this),o=["position","top","bottom","left","right","height","width"],l=e.effects.setMode(r,t.mode||"hide"),h="show"===l,u=t.direction||"vertical",d="vertical"===u,c=d?"height":"width",p=d?"top":"left",f={};e.effects.save(r,o),r.show(),a=e.effects.createWrapper(r).css({overflow:"hidden"}),s="IMG"===r[0].tagName?a:r,n=s[c](),h&&(s.css(c,0),s.css(p,n/2)),f[c]=h?n:0,f[p]=h?0:n/2,s.animate(f,{queue:!1,duration:t.duration,easing:t.easing,complete:function(){h||r.hide(),e.effects.restore(r,o),e.effects.removeWrapper(r),i()}})}})(jQuery);(function(e){e.effects.effect.drop=function(t,i){var a,s=e(this),n=["position","top","bottom","left","right","opacity","height","width"],r=e.effects.setMode(s,t.mode||"hide"),o="show"===r,l=t.direction||"left",h="up"===l||"down"===l?"top":"left",u="up"===l||"left"===l?"pos":"neg",d={opacity:o?1:0};e.effects.save(s,n),s.show(),e.effects.createWrapper(s),a=t.distance||s["top"===h?"outerHeight":"outerWidth"](!0)/2,o&&s.css("opacity",0).css(h,"pos"===u?-a:a),d[h]=(o?"pos"===u?"+=":"-=":"pos"===u?"-=":"+=")+a,s.animate(d,{queue:!1,duration:t.duration,easing:t.easing,complete:function(){"hide"===r&&s.hide(),e.effects.restore(s,n),e.effects.removeWrapper(s),i()}})}})(jQuery);(function(e){e.effects.effect.fade=function(t,i){var s=e(this),a=e.effects.setMode(s,t.mode||"toggle");s.animate({opacity:a},{queue:!1,duration:t.duration,easing:t.easing,complete:i})}})(jQuery);(function(e){e.effects.effect.fold=function(t,i){var s,a,n=e(this),r=["position","top","bottom","left","right","height","width"],o=e.effects.setMode(n,t.mode||"hide"),l="show"===o,h="hide"===o,u=t.size||15,d=/([0-9]+)%/.exec(u),c=!!t.horizFirst,p=l!==c,f=p?["width","height"]:["height","width"],m=t.duration/2,g={},v={};e.effects.save(n,r),n.show(),s=e.effects.createWrapper(n).css({overflow:"hidden"}),a=p?[s.width(),s.height()]:[s.height(),s.width()],d&&(u=parseInt(d[1],10)/100*a[h?0:1]),l&&s.css(c?{height:0,width:u}:{height:u,width:0}),g[f[0]]=l?a[0]:u,v[f[1]]=l?a[1]:0,s.animate(g,m,t.easing).animate(v,m,t.easing,function(){h&&n.hide(),e.effects.restore(n,r),e.effects.removeWrapper(n),i()})}})(jQuery);(function(e){e.effects.effect.highlight=function(t,i){var s=e(this),a=["backgroundImage","backgroundColor","opacity"],n=e.effects.setMode(s,t.mode||"show"),r={backgroundColor:s.css("backgroundColor")};"hide"===n&&(r.opacity=0),e.effects.save(s,a),s.show().css({backgroundImage:"none",backgroundColor:t.color||"#ffff99"}).animate(r,{queue:!1,duration:t.duration,easing:t.easing,complete:function(){"hide"===n&&s.hide(),e.effects.restore(s,a),i()}})}})(jQuery);(function(e){e.effects.effect.puff=function(t,i){var s=e(this),a=e.effects.setMode(s,t.mode||"hide"),n="hide"===a,r=parseInt(t.percent,10)||150,o=r/100,h={height:s.height(),width:s.width(),outerHeight:s.outerHeight(),outerWidth:s.outerWidth()};e.extend(t,{effect:"scale",queue:!1,fade:!0,mode:a,complete:i,percent:n?r:100,from:n?h:{height:h.height*o,width:h.width*o,outerHeight:h.outerHeight*o,outerWidth:h.outerWidth*o}}),s.effect(t)},e.effects.effect.scale=function(t,i){var s=e(this),a=e.extend(!0,{},t),n=e.effects.setMode(s,t.mode||"effect"),r=parseInt(t.percent,10)||(0===parseInt(t.percent,10)?0:"hide"===n?0:100),o=t.direction||"both",h=t.origin,l={height:s.height(),width:s.width(),outerHeight:s.outerHeight(),outerWidth:s.outerWidth()},u={y:"horizontal"!==o?r/100:1,x:"vertical"!==o?r/100:1};a.effect="size",a.queue=!1,a.complete=i,"effect"!==n&&(a.origin=h||["middle","center"],a.restore=!0),a.from=t.from||("show"===n?{height:0,width:0,outerHeight:0,outerWidth:0}:l),a.to={height:l.height*u.y,width:l.width*u.x,outerHeight:l.outerHeight*u.y,outerWidth:l.outerWidth*u.x},a.fade&&("show"===n&&(a.from.opacity=0,a.to.opacity=1),"hide"===n&&(a.from.opacity=1,a.to.opacity=0)),s.effect(a)},e.effects.effect.size=function(t,i){var s,a,n,r=e(this),o=["position","top","bottom","left","right","width","height","overflow","opacity"],h=["position","top","bottom","left","right","overflow","opacity"],l=["width","height","overflow"],u=["fontSize"],d=["borderTopWidth","borderBottomWidth","paddingTop","paddingBottom"],c=["borderLeftWidth","borderRightWidth","paddingLeft","paddingRight"],p=e.effects.setMode(r,t.mode||"effect"),f=t.restore||"effect"!==p,m=t.scale||"both",g=t.origin||["middle","center"],v=r.css("position"),y=f?o:h,b={height:0,width:0,outerHeight:0,outerWidth:0};"show"===p&&r.show(),s={height:r.height(),width:r.width(),outerHeight:r.outerHeight(),outerWidth:r.outerWidth()},"toggle"===t.mode&&"show"===p?(r.from=t.to||b,r.to=t.from||s):(r.from=t.from||("show"===p?b:s),r.to=t.to||("hide"===p?b:s)),n={from:{y:r.from.height/s.height,x:r.from.width/s.width},to:{y:r.to.height/s.height,x:r.to.width/s.width}},("box"===m||"both"===m)&&(n.from.y!==n.to.y&&(y=y.concat(d),r.from=e.effects.setTransition(r,d,n.from.y,r.from),r.to=e.effects.setTransition(r,d,n.to.y,r.to)),n.from.x!==n.to.x&&(y=y.concat(c),r.from=e.effects.setTransition(r,c,n.from.x,r.from),r.to=e.effects.setTransition(r,c,n.to.x,r.to))),("content"===m||"both"===m)&&n.from.y!==n.to.y&&(y=y.concat(u).concat(l),r.from=e.effects.setTransition(r,u,n.from.y,r.from),r.to=e.effects.setTransition(r,u,n.to.y,r.to)),e.effects.save(r,y),r.show(),e.effects.createWrapper(r),r.css("overflow","hidden").css(r.from),g&&(a=e.effects.getBaseline(g,s),r.from.top=(s.outerHeight-r.outerHeight())*a.y,r.from.left=(s.outerWidth-r.outerWidth())*a.x,r.to.top=(s.outerHeight-r.to.outerHeight)*a.y,r.to.left=(s.outerWidth-r.to.outerWidth)*a.x),r.css(r.from),("content"===m||"both"===m)&&(d=d.concat(["marginTop","marginBottom"]).concat(u),c=c.concat(["marginLeft","marginRight"]),l=o.concat(d).concat(c),r.find("*[width]").each(function(){var i=e(this),s={height:i.height(),width:i.width(),outerHeight:i.outerHeight(),outerWidth:i.outerWidth()};f&&e.effects.save(i,l),i.from={height:s.height*n.from.y,width:s.width*n.from.x,outerHeight:s.outerHeight*n.from.y,outerWidth:s.outerWidth*n.from.x},i.to={height:s.height*n.to.y,width:s.width*n.to.x,outerHeight:s.height*n.to.y,outerWidth:s.width*n.to.x},n.from.y!==n.to.y&&(i.from=e.effects.setTransition(i,d,n.from.y,i.from),i.to=e.effects.setTransition(i,d,n.to.y,i.to)),n.from.x!==n.to.x&&(i.from=e.effects.setTransition(i,c,n.from.x,i.from),i.to=e.effects.setTransition(i,c,n.to.x,i.to)),i.css(i.from),i.animate(i.to,t.duration,t.easing,function(){f&&e.effects.restore(i,l)})})),r.animate(r.to,{queue:!1,duration:t.duration,easing:t.easing,complete:function(){0===r.to.opacity&&r.css("opacity",r.from.opacity),"hide"===p&&r.hide(),e.effects.restore(r,y),f||("static"===v?r.css({position:"relative",top:r.to.top,left:r.to.left}):e.each(["top","left"],function(e,t){r.css(t,function(t,i){var s=parseInt(i,10),a=e?r.to.left:r.to.top;return"auto"===i?a+"px":s+a+"px"})})),e.effects.removeWrapper(r),i()}})}})(jQuery);(function(e){e.effects.effect.slide=function(t,i){var s,a=e(this),n=["position","top","bottom","left","right","width","height"],r=e.effects.setMode(a,t.mode||"show"),o="show"===r,h=t.direction||"left",l="up"===h||"down"===h?"top":"left",u="up"===h||"left"===h,d={};e.effects.save(a,n),a.show(),s=t.distance||a["top"===l?"outerHeight":"outerWidth"](!0),e.effects.createWrapper(a).css({overflow:"hidden"}),o&&a.css(l,u?isNaN(s)?"-"+s:-s:s),d[l]=(o?u?"+=":"-=":u?"-=":"+=")+s,a.animate(d,{queue:!1,duration:t.duration,easing:t.easing,complete:function(){"hide"===r&&a.hide(),e.effects.restore(a,n),e.effects.removeWrapper(a),i()}})}})(jQuery);
	</script>
<?php endif; ?>
	
	
<!-- ========================================
KNOCKOUT ASSETS -->
<?php if( DupUtil::is_url_active("ajax.aspnetcdn.com", 443) ): ?>
	<script src="//ajax.aspnetcdn.com/ajax/knockout/knockout-2.2.1.js"></script>
<?php else: ?>	
	
	<script type="text/javascript">
	// Knockout JavaScript library v2.2.1
	// (c) Steven Sanderson - http://knockoutjs.com/
	// License: MIT (http://www.opensource.org/licenses/mit-license.php)

	(function() {function j(w){throw w;}var m=!0,p=null,r=!1;function u(w){return function(){return w}};var x=window,y=document,ga=navigator,F=window.jQuery,I=void 0;
	function L(w){function ha(a,d,c,e,f){var g=[];a=b.j(function(){var a=d(c,f)||[];0<g.length&&(b.a.Ya(M(g),a),e&&b.r.K(e,p,[c,a,f]));g.splice(0,g.length);b.a.P(g,a)},p,{W:a,Ka:function(){return 0==g.length||!b.a.X(g[0])}});return{M:g,j:a.pa()?a:I}}function M(a){for(;a.length&&!b.a.X(a[0]);)a.splice(0,1);if(1<a.length){for(var d=a[0],c=a[a.length-1],e=[d];d!==c;){d=d.nextSibling;if(!d)return;e.push(d)}Array.prototype.splice.apply(a,[0,a.length].concat(e))}return a}function S(a,b,c,e,f){var g=Math.min,
	h=Math.max,k=[],l,n=a.length,q,s=b.length,v=s-n||1,G=n+s+1,J,A,z;for(l=0;l<=n;l++){A=J;k.push(J=[]);z=g(s,l+v);for(q=h(0,l-1);q<=z;q++)J[q]=q?l?a[l-1]===b[q-1]?A[q-1]:g(A[q]||G,J[q-1]||G)+1:q+1:l+1}g=[];h=[];v=[];l=n;for(q=s;l||q;)s=k[l][q]-1,q&&s===k[l][q-1]?h.push(g[g.length]={status:c,value:b[--q],index:q}):l&&s===k[l-1][q]?v.push(g[g.length]={status:e,value:a[--l],index:l}):(g.push({status:"retained",value:b[--q]}),--l);if(h.length&&v.length){a=10*n;var t;for(b=c=0;(f||b<a)&&(t=h[c]);c++){for(e=
	0;k=v[e];e++)if(t.value===k.value){t.moved=k.index;k.moved=t.index;v.splice(e,1);b=e=0;break}b+=e}}return g.reverse()}function T(a,d,c,e,f){f=f||{};var g=a&&N(a),g=g&&g.ownerDocument,h=f.templateEngine||O;b.za.vb(c,h,g);c=h.renderTemplate(c,e,f,g);("number"!=typeof c.length||0<c.length&&"number"!=typeof c[0].nodeType)&&j(Error("Template engine must return an array of DOM nodes"));g=r;switch(d){case "replaceChildren":b.e.N(a,c);g=m;break;case "replaceNode":b.a.Ya(a,c);g=m;break;case "ignoreTargetNode":break;
	default:j(Error("Unknown renderMode: "+d))}g&&(U(c,e),f.afterRender&&b.r.K(f.afterRender,p,[c,e.$data]));return c}function N(a){return a.nodeType?a:0<a.length?a[0]:p}function U(a,d){if(a.length){var c=a[0],e=a[a.length-1];V(c,e,function(a){b.Da(d,a)});V(c,e,function(a){b.s.ib(a,[d])})}}function V(a,d,c){var e;for(d=b.e.nextSibling(d);a&&(e=a)!==d;)a=b.e.nextSibling(e),(1===e.nodeType||8===e.nodeType)&&c(e)}function W(a,d,c){a=b.g.aa(a);for(var e=b.g.Q,f=0;f<a.length;f++){var g=a[f].key;if(e.hasOwnProperty(g)){var h=
	e[g];"function"===typeof h?(g=h(a[f].value))&&j(Error(g)):h||j(Error("This template engine does not support the '"+g+"' binding within its templates"))}}a="ko.__tr_ambtns(function($context,$element){return(function(){return{ "+b.g.ba(a)+" } })()})";return c.createJavaScriptEvaluatorBlock(a)+d}function X(a,d,c,e){function f(a){return function(){return k[a]}}function g(){return k}var h=0,k,l;b.j(function(){var n=c&&c instanceof b.z?c:new b.z(b.a.d(c)),q=n.$data;e&&b.eb(a,n);if(k=("function"==typeof d?
	d(n,a):d)||b.J.instance.getBindings(a,n)){if(0===h){h=1;for(var s in k){var v=b.c[s];v&&8===a.nodeType&&!b.e.I[s]&&j(Error("The binding '"+s+"' cannot be used with virtual elements"));if(v&&"function"==typeof v.init&&(v=(0,v.init)(a,f(s),g,q,n))&&v.controlsDescendantBindings)l!==I&&j(Error("Multiple bindings ("+l+" and "+s+") are trying to control descendant bindings of the same element. You cannot use these bindings together on the same element.")),l=s}h=2}if(2===h)for(s in k)(v=b.c[s])&&"function"==
	typeof v.update&&(0,v.update)(a,f(s),g,q,n)}},p,{W:a});return{Nb:l===I}}function Y(a,d,c){var e=m,f=1===d.nodeType;f&&b.e.Ta(d);if(f&&c||b.J.instance.nodeHasBindings(d))e=X(d,p,a,c).Nb;e&&Z(a,d,!f)}function Z(a,d,c){for(var e=b.e.firstChild(d);d=e;)e=b.e.nextSibling(d),Y(a,d,c)}function $(a,b){var c=aa(a,b);return c?0<c.length?c[c.length-1].nextSibling:a.nextSibling:p}function aa(a,b){for(var c=a,e=1,f=[];c=c.nextSibling;){if(H(c)&&(e--,0===e))return f;f.push(c);B(c)&&e++}b||j(Error("Cannot find closing comment tag to match: "+
	a.nodeValue));return p}function H(a){return 8==a.nodeType&&(K?a.text:a.nodeValue).match(ia)}function B(a){return 8==a.nodeType&&(K?a.text:a.nodeValue).match(ja)}function P(a,b){for(var c=p;a!=c;)c=a,a=a.replace(ka,function(a,c){return b[c]});return a}function la(){var a=[],d=[];this.save=function(c,e){var f=b.a.i(a,c);0<=f?d[f]=e:(a.push(c),d.push(e))};this.get=function(c){c=b.a.i(a,c);return 0<=c?d[c]:I}}function ba(a,b,c){function e(e){var g=b(a[e]);switch(typeof g){case "boolean":case "number":case "string":case "function":f[e]=
	g;break;case "object":case "undefined":var h=c.get(g);f[e]=h!==I?h:ba(g,b,c)}}c=c||new la;a=b(a);if(!("object"==typeof a&&a!==p&&a!==I&&!(a instanceof Date)))return a;var f=a instanceof Array?[]:{};c.save(a,f);var g=a;if(g instanceof Array){for(var h=0;h<g.length;h++)e(h);"function"==typeof g.toJSON&&e("toJSON")}else for(h in g)e(h);return f}function ca(a,d){if(a)if(8==a.nodeType){var c=b.s.Ua(a.nodeValue);c!=p&&d.push({sb:a,Fb:c})}else if(1==a.nodeType)for(var c=0,e=a.childNodes,f=e.length;c<f;c++)ca(e[c],
	d)}function Q(a,d,c,e){b.c[a]={init:function(a){b.a.f.set(a,da,{});return{controlsDescendantBindings:m}},update:function(a,g,h,k,l){h=b.a.f.get(a,da);g=b.a.d(g());k=!c!==!g;var n=!h.Za;if(n||d||k!==h.qb)n&&(h.Za=b.a.Ia(b.e.childNodes(a),m)),k?(n||b.e.N(a,b.a.Ia(h.Za)),b.Ea(e?e(l,g):l,a)):b.e.Y(a),h.qb=k}};b.g.Q[a]=r;b.e.I[a]=m}function ea(a,d,c){c&&d!==b.k.q(a)&&b.k.T(a,d);d!==b.k.q(a)&&b.r.K(b.a.Ba,p,[a,"change"])}var b="undefined"!==typeof w?w:{};b.b=function(a,d){for(var c=a.split("."),e=b,f=0;f<
	c.length-1;f++)e=e[c[f]];e[c[c.length-1]]=d};b.p=function(a,b,c){a[b]=c};b.version="2.2.1";b.b("version",b.version);b.a=new function(){function a(a,d){if("input"!==b.a.u(a)||!a.type||"click"!=d.toLowerCase())return r;var c=a.type;return"checkbox"==c||"radio"==c}var d=/^(\s|\u00A0)+|(\s|\u00A0)+$/g,c={},e={};c[/Firefox\/2/i.test(ga.userAgent)?"KeyboardEvent":"UIEvents"]=["keyup","keydown","keypress"];c.MouseEvents="click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave".split(" ");
	for(var f in c){var g=c[f];if(g.length)for(var h=0,k=g.length;h<k;h++)e[g[h]]=f}var l={propertychange:m},n,c=3;f=y.createElement("div");for(g=f.getElementsByTagName("i");f.innerHTML="\x3c!--[if gt IE "+ ++c+"]><i></i><![endif]--\x3e",g[0];);n=4<c?c:I;return{Na:["authenticity_token",/^__RequestVerificationToken(_.*)?$/],o:function(a,b){for(var d=0,c=a.length;d<c;d++)b(a[d])},i:function(a,b){if("function"==typeof Array.prototype.indexOf)return Array.prototype.indexOf.call(a,b);for(var d=0,c=a.length;d<
	c;d++)if(a[d]===b)return d;return-1},lb:function(a,b,d){for(var c=0,e=a.length;c<e;c++)if(b.call(d,a[c]))return a[c];return p},ga:function(a,d){var c=b.a.i(a,d);0<=c&&a.splice(c,1)},Ga:function(a){a=a||[];for(var d=[],c=0,e=a.length;c<e;c++)0>b.a.i(d,a[c])&&d.push(a[c]);return d},V:function(a,b){a=a||[];for(var d=[],c=0,e=a.length;c<e;c++)d.push(b(a[c]));return d},fa:function(a,b){a=a||[];for(var d=[],c=0,e=a.length;c<e;c++)b(a[c])&&d.push(a[c]);return d},P:function(a,b){if(b instanceof Array)a.push.apply(a,
	b);else for(var d=0,c=b.length;d<c;d++)a.push(b[d]);return a},extend:function(a,b){if(b)for(var d in b)b.hasOwnProperty(d)&&(a[d]=b[d]);return a},ka:function(a){for(;a.firstChild;)b.removeNode(a.firstChild)},Hb:function(a){a=b.a.L(a);for(var d=y.createElement("div"),c=0,e=a.length;c<e;c++)d.appendChild(b.A(a[c]));return d},Ia:function(a,d){for(var c=0,e=a.length,g=[];c<e;c++){var f=a[c].cloneNode(m);g.push(d?b.A(f):f)}return g},N:function(a,d){b.a.ka(a);if(d)for(var c=0,e=d.length;c<e;c++)a.appendChild(d[c])},
	Ya:function(a,d){var c=a.nodeType?[a]:a;if(0<c.length){for(var e=c[0],g=e.parentNode,f=0,h=d.length;f<h;f++)g.insertBefore(d[f],e);f=0;for(h=c.length;f<h;f++)b.removeNode(c[f])}},bb:function(a,b){7>n?a.setAttribute("selected",b):a.selected=b},D:function(a){return(a||"").replace(d,"")},Rb:function(a,d){for(var c=[],e=(a||"").split(d),f=0,g=e.length;f<g;f++){var h=b.a.D(e[f]);""!==h&&c.push(h)}return c},Ob:function(a,b){a=a||"";return b.length>a.length?r:a.substring(0,b.length)===b},tb:function(a,b){if(b.compareDocumentPosition)return 16==
	(b.compareDocumentPosition(a)&16);for(;a!=p;){if(a==b)return m;a=a.parentNode}return r},X:function(a){return b.a.tb(a,a.ownerDocument)},u:function(a){return a&&a.tagName&&a.tagName.toLowerCase()},n:function(b,d,c){var e=n&&l[d];if(!e&&"undefined"!=typeof F){if(a(b,d)){var f=c;c=function(a,b){var d=this.checked;b&&(this.checked=b.nb!==m);f.call(this,a);this.checked=d}}F(b).bind(d,c)}else!e&&"function"==typeof b.addEventListener?b.addEventListener(d,c,r):"undefined"!=typeof b.attachEvent?b.attachEvent("on"+
	d,function(a){c.call(b,a)}):j(Error("Browser doesn't support addEventListener or attachEvent"))},Ba:function(b,d){(!b||!b.nodeType)&&j(Error("element must be a DOM node when calling triggerEvent"));if("undefined"!=typeof F){var c=[];a(b,d)&&c.push({nb:b.checked});F(b).trigger(d,c)}else"function"==typeof y.createEvent?"function"==typeof b.dispatchEvent?(c=y.createEvent(e[d]||"HTMLEvents"),c.initEvent(d,m,m,x,0,0,0,0,0,r,r,r,r,0,b),b.dispatchEvent(c)):j(Error("The supplied element doesn't support dispatchEvent")):
	"undefined"!=typeof b.fireEvent?(a(b,d)&&(b.checked=b.checked!==m),b.fireEvent("on"+d)):j(Error("Browser doesn't support triggering events"))},d:function(a){return b.$(a)?a():a},ua:function(a){return b.$(a)?a.t():a},da:function(a,d,c){if(d){var e=/[\w-]+/g,f=a.className.match(e)||[];b.a.o(d.match(e),function(a){var d=b.a.i(f,a);0<=d?c||f.splice(d,1):c&&f.push(a)});a.className=f.join(" ")}},cb:function(a,d){var c=b.a.d(d);if(c===p||c===I)c="";if(3===a.nodeType)a.data=c;else{var e=b.e.firstChild(a);
	!e||3!=e.nodeType||b.e.nextSibling(e)?b.e.N(a,[y.createTextNode(c)]):e.data=c;b.a.wb(a)}},ab:function(a,b){a.name=b;if(7>=n)try{a.mergeAttributes(y.createElement("<input name='"+a.name+"'/>"),r)}catch(d){}},wb:function(a){9<=n&&(a=1==a.nodeType?a:a.parentNode,a.style&&(a.style.zoom=a.style.zoom))},ub:function(a){if(9<=n){var b=a.style.width;a.style.width=0;a.style.width=b}},Lb:function(a,d){a=b.a.d(a);d=b.a.d(d);for(var c=[],e=a;e<=d;e++)c.push(e);return c},L:function(a){for(var b=[],d=0,c=a.length;d<
	c;d++)b.push(a[d]);return b},Pb:6===n,Qb:7===n,Z:n,Oa:function(a,d){for(var c=b.a.L(a.getElementsByTagName("input")).concat(b.a.L(a.getElementsByTagName("textarea"))),e="string"==typeof d?function(a){return a.name===d}:function(a){return d.test(a.name)},f=[],g=c.length-1;0<=g;g--)e(c[g])&&f.push(c[g]);return f},Ib:function(a){return"string"==typeof a&&(a=b.a.D(a))?x.JSON&&x.JSON.parse?x.JSON.parse(a):(new Function("return "+a))():p},xa:function(a,d,c){("undefined"==typeof JSON||"undefined"==typeof JSON.stringify)&&
	j(Error("Cannot find JSON.stringify(). Some browsers (e.g., IE < 8) don't support it natively, but you can overcome this by adding a script reference to json2.js, downloadable from http://www.json.org/json2.js"));return JSON.stringify(b.a.d(a),d,c)},Jb:function(a,d,c){c=c||{};var e=c.params||{},f=c.includeFields||this.Na,g=a;if("object"==typeof a&&"form"===b.a.u(a))for(var g=a.action,h=f.length-1;0<=h;h--)for(var k=b.a.Oa(a,f[h]),l=k.length-1;0<=l;l--)e[k[l].name]=k[l].value;d=b.a.d(d);var n=y.createElement("form");
	n.style.display="none";n.action=g;n.method="post";for(var w in d)a=y.createElement("input"),a.name=w,a.value=b.a.xa(b.a.d(d[w])),n.appendChild(a);for(w in e)a=y.createElement("input"),a.name=w,a.value=e[w],n.appendChild(a);y.body.appendChild(n);c.submitter?c.submitter(n):n.submit();setTimeout(function(){n.parentNode.removeChild(n)},0)}}};b.b("utils",b.a);b.b("utils.arrayForEach",b.a.o);b.b("utils.arrayFirst",b.a.lb);b.b("utils.arrayFilter",b.a.fa);b.b("utils.arrayGetDistinctValues",b.a.Ga);b.b("utils.arrayIndexOf",
	b.a.i);b.b("utils.arrayMap",b.a.V);b.b("utils.arrayPushAll",b.a.P);b.b("utils.arrayRemoveItem",b.a.ga);b.b("utils.extend",b.a.extend);b.b("utils.fieldsIncludedWithJsonPost",b.a.Na);b.b("utils.getFormFields",b.a.Oa);b.b("utils.peekObservable",b.a.ua);b.b("utils.postJson",b.a.Jb);b.b("utils.parseJson",b.a.Ib);b.b("utils.registerEventHandler",b.a.n);b.b("utils.stringifyJson",b.a.xa);b.b("utils.range",b.a.Lb);b.b("utils.toggleDomNodeCssClass",b.a.da);b.b("utils.triggerEvent",b.a.Ba);b.b("utils.unwrapObservable",
	b.a.d);Function.prototype.bind||(Function.prototype.bind=function(a){var b=this,c=Array.prototype.slice.call(arguments);a=c.shift();return function(){return b.apply(a,c.concat(Array.prototype.slice.call(arguments)))}});b.a.f=new function(){var a=0,d="__ko__"+(new Date).getTime(),c={};return{get:function(a,d){var c=b.a.f.la(a,r);return c===I?I:c[d]},set:function(a,d,c){c===I&&b.a.f.la(a,r)===I||(b.a.f.la(a,m)[d]=c)},la:function(b,f){var g=b[d];if(!g||!("null"!==g&&c[g])){if(!f)return I;g=b[d]="ko"+
	a++;c[g]={}}return c[g]},clear:function(a){var b=a[d];return b?(delete c[b],a[d]=p,m):r}}};b.b("utils.domData",b.a.f);b.b("utils.domData.clear",b.a.f.clear);b.a.F=new function(){function a(a,d){var e=b.a.f.get(a,c);e===I&&d&&(e=[],b.a.f.set(a,c,e));return e}function d(c){var e=a(c,r);if(e)for(var e=e.slice(0),k=0;k<e.length;k++)e[k](c);b.a.f.clear(c);"function"==typeof F&&"function"==typeof F.cleanData&&F.cleanData([c]);if(f[c.nodeType])for(e=c.firstChild;c=e;)e=c.nextSibling,8===c.nodeType&&d(c)}
	var c="__ko_domNodeDisposal__"+(new Date).getTime(),e={1:m,8:m,9:m},f={1:m,9:m};return{Ca:function(b,d){"function"!=typeof d&&j(Error("Callback must be a function"));a(b,m).push(d)},Xa:function(d,e){var f=a(d,r);f&&(b.a.ga(f,e),0==f.length&&b.a.f.set(d,c,I))},A:function(a){if(e[a.nodeType]&&(d(a),f[a.nodeType])){var c=[];b.a.P(c,a.getElementsByTagName("*"));for(var k=0,l=c.length;k<l;k++)d(c[k])}return a},removeNode:function(a){b.A(a);a.parentNode&&a.parentNode.removeChild(a)}}};b.A=b.a.F.A;b.removeNode=
	b.a.F.removeNode;b.b("cleanNode",b.A);b.b("removeNode",b.removeNode);b.b("utils.domNodeDisposal",b.a.F);b.b("utils.domNodeDisposal.addDisposeCallback",b.a.F.Ca);b.b("utils.domNodeDisposal.removeDisposeCallback",b.a.F.Xa);b.a.ta=function(a){var d;if("undefined"!=typeof F)if(F.parseHTML)d=F.parseHTML(a);else{if((d=F.clean([a]))&&d[0]){for(a=d[0];a.parentNode&&11!==a.parentNode.nodeType;)a=a.parentNode;a.parentNode&&a.parentNode.removeChild(a)}}else{var c=b.a.D(a).toLowerCase();d=y.createElement("div");
	c=c.match(/^<(thead|tbody|tfoot)/)&&[1,"<table>","</table>"]||!c.indexOf("<tr")&&[2,"<table><tbody>","</tbody></table>"]||(!c.indexOf("<td")||!c.indexOf("<th"))&&[3,"<table><tbody><tr>","</tr></tbody></table>"]||[0,"",""];a="ignored<div>"+c[1]+a+c[2]+"</div>";for("function"==typeof x.innerShiv?d.appendChild(x.innerShiv(a)):d.innerHTML=a;c[0]--;)d=d.lastChild;d=b.a.L(d.lastChild.childNodes)}return d};b.a.ca=function(a,d){b.a.ka(a);d=b.a.d(d);if(d!==p&&d!==I)if("string"!=typeof d&&(d=d.toString()),
	"undefined"!=typeof F)F(a).html(d);else for(var c=b.a.ta(d),e=0;e<c.length;e++)a.appendChild(c[e])};b.b("utils.parseHtmlFragment",b.a.ta);b.b("utils.setHtml",b.a.ca);var R={};b.s={ra:function(a){"function"!=typeof a&&j(Error("You can only pass a function to ko.memoization.memoize()"));var b=(4294967296*(1+Math.random())|0).toString(16).substring(1)+(4294967296*(1+Math.random())|0).toString(16).substring(1);R[b]=a;return"\x3c!--[ko_memo:"+b+"]--\x3e"},hb:function(a,b){var c=R[a];c===I&&j(Error("Couldn't find any memo with ID "+
	a+". Perhaps it's already been unmemoized."));try{return c.apply(p,b||[]),m}finally{delete R[a]}},ib:function(a,d){var c=[];ca(a,c);for(var e=0,f=c.length;e<f;e++){var g=c[e].sb,h=[g];d&&b.a.P(h,d);b.s.hb(c[e].Fb,h);g.nodeValue="";g.parentNode&&g.parentNode.removeChild(g)}},Ua:function(a){return(a=a.match(/^\[ko_memo\:(.*?)\]$/))?a[1]:p}};b.b("memoization",b.s);b.b("memoization.memoize",b.s.ra);b.b("memoization.unmemoize",b.s.hb);b.b("memoization.parseMemoText",b.s.Ua);b.b("memoization.unmemoizeDomNodeAndDescendants",
	b.s.ib);b.Ma={throttle:function(a,d){a.throttleEvaluation=d;var c=p;return b.j({read:a,write:function(b){clearTimeout(c);c=setTimeout(function(){a(b)},d)}})},notify:function(a,d){a.equalityComparer="always"==d?u(r):b.m.fn.equalityComparer;return a}};b.b("extenders",b.Ma);b.fb=function(a,d,c){this.target=a;this.ha=d;this.rb=c;b.p(this,"dispose",this.B)};b.fb.prototype.B=function(){this.Cb=m;this.rb()};b.S=function(){this.w={};b.a.extend(this,b.S.fn);b.p(this,"subscribe",this.ya);b.p(this,"extend",
	this.extend);b.p(this,"getSubscriptionsCount",this.yb)};b.S.fn={ya:function(a,d,c){c=c||"change";var e=new b.fb(this,d?a.bind(d):a,function(){b.a.ga(this.w[c],e)}.bind(this));this.w[c]||(this.w[c]=[]);this.w[c].push(e);return e},notifySubscribers:function(a,d){d=d||"change";this.w[d]&&b.r.K(function(){b.a.o(this.w[d].slice(0),function(b){b&&b.Cb!==m&&b.ha(a)})},this)},yb:function(){var a=0,b;for(b in this.w)this.w.hasOwnProperty(b)&&(a+=this.w[b].length);return a},extend:function(a){var d=this;if(a)for(var c in a){var e=
	b.Ma[c];"function"==typeof e&&(d=e(d,a[c]))}return d}};b.Qa=function(a){return"function"==typeof a.ya&&"function"==typeof a.notifySubscribers};b.b("subscribable",b.S);b.b("isSubscribable",b.Qa);var C=[];b.r={mb:function(a){C.push({ha:a,La:[]})},end:function(){C.pop()},Wa:function(a){b.Qa(a)||j(Error("Only subscribable things can act as dependencies"));if(0<C.length){var d=C[C.length-1];d&&!(0<=b.a.i(d.La,a))&&(d.La.push(a),d.ha(a))}},K:function(a,b,c){try{return C.push(p),a.apply(b,c||[])}finally{C.pop()}}};
	var ma={undefined:m,"boolean":m,number:m,string:m};b.m=function(a){function d(){if(0<arguments.length){if(!d.equalityComparer||!d.equalityComparer(c,arguments[0]))d.H(),c=arguments[0],d.G();return this}b.r.Wa(d);return c}var c=a;b.S.call(d);d.t=function(){return c};d.G=function(){d.notifySubscribers(c)};d.H=function(){d.notifySubscribers(c,"beforeChange")};b.a.extend(d,b.m.fn);b.p(d,"peek",d.t);b.p(d,"valueHasMutated",d.G);b.p(d,"valueWillMutate",d.H);return d};b.m.fn={equalityComparer:function(a,
	b){return a===p||typeof a in ma?a===b:r}};var E=b.m.Kb="__ko_proto__";b.m.fn[E]=b.m;b.ma=function(a,d){return a===p||a===I||a[E]===I?r:a[E]===d?m:b.ma(a[E],d)};b.$=function(a){return b.ma(a,b.m)};b.Ra=function(a){return"function"==typeof a&&a[E]===b.m||"function"==typeof a&&a[E]===b.j&&a.zb?m:r};b.b("observable",b.m);b.b("isObservable",b.$);b.b("isWriteableObservable",b.Ra);b.R=function(a){0==arguments.length&&(a=[]);a!==p&&(a!==I&&!("length"in a))&&j(Error("The argument passed when initializing an observable array must be an array, or null, or undefined."));
	var d=b.m(a);b.a.extend(d,b.R.fn);return d};b.R.fn={remove:function(a){for(var b=this.t(),c=[],e="function"==typeof a?a:function(b){return b===a},f=0;f<b.length;f++){var g=b[f];e(g)&&(0===c.length&&this.H(),c.push(g),b.splice(f,1),f--)}c.length&&this.G();return c},removeAll:function(a){if(a===I){var d=this.t(),c=d.slice(0);this.H();d.splice(0,d.length);this.G();return c}return!a?[]:this.remove(function(d){return 0<=b.a.i(a,d)})},destroy:function(a){var b=this.t(),c="function"==typeof a?a:function(b){return b===
	a};this.H();for(var e=b.length-1;0<=e;e--)c(b[e])&&(b[e]._destroy=m);this.G()},destroyAll:function(a){return a===I?this.destroy(u(m)):!a?[]:this.destroy(function(d){return 0<=b.a.i(a,d)})},indexOf:function(a){var d=this();return b.a.i(d,a)},replace:function(a,b){var c=this.indexOf(a);0<=c&&(this.H(),this.t()[c]=b,this.G())}};b.a.o("pop push reverse shift sort splice unshift".split(" "),function(a){b.R.fn[a]=function(){var b=this.t();this.H();b=b[a].apply(b,arguments);this.G();return b}});b.a.o(["slice"],
	function(a){b.R.fn[a]=function(){var b=this();return b[a].apply(b,arguments)}});b.b("observableArray",b.R);b.j=function(a,d,c){function e(){b.a.o(z,function(a){a.B()});z=[]}function f(){var a=h.throttleEvaluation;a&&0<=a?(clearTimeout(t),t=setTimeout(g,a)):g()}function g(){if(!q)if(n&&w())A();else{q=m;try{var a=b.a.V(z,function(a){return a.target});b.r.mb(function(c){var d;0<=(d=b.a.i(a,c))?a[d]=I:z.push(c.ya(f))});for(var c=s.call(d),e=a.length-1;0<=e;e--)a[e]&&z.splice(e,1)[0].B();n=m;h.notifySubscribers(l,
	"beforeChange");l=c}finally{b.r.end()}h.notifySubscribers(l);q=r;z.length||A()}}function h(){if(0<arguments.length)return"function"===typeof v?v.apply(d,arguments):j(Error("Cannot write a value to a ko.computed unless you specify a 'write' option. If you wish to read the current value, don't pass any parameters.")),this;n||g();b.r.Wa(h);return l}function k(){return!n||0<z.length}var l,n=r,q=r,s=a;s&&"object"==typeof s?(c=s,s=c.read):(c=c||{},s||(s=c.read));"function"!=typeof s&&j(Error("Pass a function that returns the value of the ko.computed"));
	var v=c.write,G=c.disposeWhenNodeIsRemoved||c.W||p,w=c.disposeWhen||c.Ka||u(r),A=e,z=[],t=p;d||(d=c.owner);h.t=function(){n||g();return l};h.xb=function(){return z.length};h.zb="function"===typeof c.write;h.B=function(){A()};h.pa=k;b.S.call(h);b.a.extend(h,b.j.fn);b.p(h,"peek",h.t);b.p(h,"dispose",h.B);b.p(h,"isActive",h.pa);b.p(h,"getDependenciesCount",h.xb);c.deferEvaluation!==m&&g();if(G&&k()){A=function(){b.a.F.Xa(G,arguments.callee);e()};b.a.F.Ca(G,A);var D=w,w=function(){return!b.a.X(G)||D()}}return h};
	b.Bb=function(a){return b.ma(a,b.j)};w=b.m.Kb;b.j[w]=b.m;b.j.fn={};b.j.fn[w]=b.j;b.b("dependentObservable",b.j);b.b("computed",b.j);b.b("isComputed",b.Bb);b.gb=function(a){0==arguments.length&&j(Error("When calling ko.toJS, pass the object you want to convert."));return ba(a,function(a){for(var c=0;b.$(a)&&10>c;c++)a=a();return a})};b.toJSON=function(a,d,c){a=b.gb(a);return b.a.xa(a,d,c)};b.b("toJS",b.gb);b.b("toJSON",b.toJSON);b.k={q:function(a){switch(b.a.u(a)){case "option":return a.__ko__hasDomDataOptionValue__===
	m?b.a.f.get(a,b.c.options.sa):7>=b.a.Z?a.getAttributeNode("value").specified?a.value:a.text:a.value;case "select":return 0<=a.selectedIndex?b.k.q(a.options[a.selectedIndex]):I;default:return a.value}},T:function(a,d){switch(b.a.u(a)){case "option":switch(typeof d){case "string":b.a.f.set(a,b.c.options.sa,I);"__ko__hasDomDataOptionValue__"in a&&delete a.__ko__hasDomDataOptionValue__;a.value=d;break;default:b.a.f.set(a,b.c.options.sa,d),a.__ko__hasDomDataOptionValue__=m,a.value="number"===typeof d?
	d:""}break;case "select":for(var c=a.options.length-1;0<=c;c--)if(b.k.q(a.options[c])==d){a.selectedIndex=c;break}break;default:if(d===p||d===I)d="";a.value=d}}};b.b("selectExtensions",b.k);b.b("selectExtensions.readValue",b.k.q);b.b("selectExtensions.writeValue",b.k.T);var ka=/\@ko_token_(\d+)\@/g,na=["true","false"],oa=/^(?:[$_a-z][$\w]*|(.+)(\.\s*[$_a-z][$\w]*|\[.+\]))$/i;b.g={Q:[],aa:function(a){var d=b.a.D(a);if(3>d.length)return[];"{"===d.charAt(0)&&(d=d.substring(1,d.length-1));a=[];for(var c=
	p,e,f=0;f<d.length;f++){var g=d.charAt(f);if(c===p)switch(g){case '"':case "'":case "/":c=f,e=g}else if(g==e&&"\\"!==d.charAt(f-1)){g=d.substring(c,f+1);a.push(g);var h="@ko_token_"+(a.length-1)+"@",d=d.substring(0,c)+h+d.substring(f+1),f=f-(g.length-h.length),c=p}}e=c=p;for(var k=0,l=p,f=0;f<d.length;f++){g=d.charAt(f);if(c===p)switch(g){case "{":c=f;l=g;e="}";break;case "(":c=f;l=g;e=")";break;case "[":c=f,l=g,e="]"}g===l?k++:g===e&&(k--,0===k&&(g=d.substring(c,f+1),a.push(g),h="@ko_token_"+(a.length-
	1)+"@",d=d.substring(0,c)+h+d.substring(f+1),f-=g.length-h.length,c=p))}e=[];d=d.split(",");c=0;for(f=d.length;c<f;c++)k=d[c],l=k.indexOf(":"),0<l&&l<k.length-1?(g=k.substring(l+1),e.push({key:P(k.substring(0,l),a),value:P(g,a)})):e.push({unknown:P(k,a)});return e},ba:function(a){var d="string"===typeof a?b.g.aa(a):a,c=[];a=[];for(var e,f=0;e=d[f];f++)if(0<c.length&&c.push(","),e.key){var g;a:{g=e.key;var h=b.a.D(g);switch(h.length&&h.charAt(0)){case "'":case '"':break a;default:g="'"+h+"'"}}e=e.value;
	c.push(g);c.push(":");c.push(e);e=b.a.D(e);0<=b.a.i(na,b.a.D(e).toLowerCase())?e=r:(h=e.match(oa),e=h===p?r:h[1]?"Object("+h[1]+")"+h[2]:e);e&&(0<a.length&&a.push(", "),a.push(g+" : function(__ko_value) { "+e+" = __ko_value; }"))}else e.unknown&&c.push(e.unknown);d=c.join("");0<a.length&&(d=d+", '_ko_property_writers' : { "+a.join("")+" } ");return d},Eb:function(a,d){for(var c=0;c<a.length;c++)if(b.a.D(a[c].key)==d)return m;return r},ea:function(a,d,c,e,f){if(!a||!b.Ra(a)){if((a=d()._ko_property_writers)&&
	a[c])a[c](e)}else(!f||a.t()!==e)&&a(e)}};b.b("expressionRewriting",b.g);b.b("expressionRewriting.bindingRewriteValidators",b.g.Q);b.b("expressionRewriting.parseObjectLiteral",b.g.aa);b.b("expressionRewriting.preProcessBindings",b.g.ba);b.b("jsonExpressionRewriting",b.g);b.b("jsonExpressionRewriting.insertPropertyAccessorsIntoJson",b.g.ba);var K="\x3c!--test--\x3e"===y.createComment("test").text,ja=K?/^\x3c!--\s*ko(?:\s+(.+\s*\:[\s\S]*))?\s*--\x3e$/:/^\s*ko(?:\s+(.+\s*\:[\s\S]*))?\s*$/,ia=K?/^\x3c!--\s*\/ko\s*--\x3e$/:
	/^\s*\/ko\s*$/,pa={ul:m,ol:m};b.e={I:{},childNodes:function(a){return B(a)?aa(a):a.childNodes},Y:function(a){if(B(a)){a=b.e.childNodes(a);for(var d=0,c=a.length;d<c;d++)b.removeNode(a[d])}else b.a.ka(a)},N:function(a,d){if(B(a)){b.e.Y(a);for(var c=a.nextSibling,e=0,f=d.length;e<f;e++)c.parentNode.insertBefore(d[e],c)}else b.a.N(a,d)},Va:function(a,b){B(a)?a.parentNode.insertBefore(b,a.nextSibling):a.firstChild?a.insertBefore(b,a.firstChild):a.appendChild(b)},Pa:function(a,d,c){c?B(a)?a.parentNode.insertBefore(d,
	c.nextSibling):c.nextSibling?a.insertBefore(d,c.nextSibling):a.appendChild(d):b.e.Va(a,d)},firstChild:function(a){return!B(a)?a.firstChild:!a.nextSibling||H(a.nextSibling)?p:a.nextSibling},nextSibling:function(a){B(a)&&(a=$(a));return a.nextSibling&&H(a.nextSibling)?p:a.nextSibling},jb:function(a){return(a=B(a))?a[1]:p},Ta:function(a){if(pa[b.a.u(a)]){var d=a.firstChild;if(d){do if(1===d.nodeType){var c;c=d.firstChild;var e=p;if(c){do if(e)e.push(c);else if(B(c)){var f=$(c,m);f?c=f:e=[c]}else H(c)&&
	(e=[c]);while(c=c.nextSibling)}if(c=e){e=d.nextSibling;for(f=0;f<c.length;f++)e?a.insertBefore(c[f],e):a.appendChild(c[f])}}while(d=d.nextSibling)}}}};b.b("virtualElements",b.e);b.b("virtualElements.allowedBindings",b.e.I);b.b("virtualElements.emptyNode",b.e.Y);b.b("virtualElements.insertAfter",b.e.Pa);b.b("virtualElements.prepend",b.e.Va);b.b("virtualElements.setDomNodeChildren",b.e.N);b.J=function(){this.Ha={}};b.a.extend(b.J.prototype,{nodeHasBindings:function(a){switch(a.nodeType){case 1:return a.getAttribute("data-bind")!=
	p;case 8:return b.e.jb(a)!=p;default:return r}},getBindings:function(a,b){var c=this.getBindingsString(a,b);return c?this.parseBindingsString(c,b,a):p},getBindingsString:function(a){switch(a.nodeType){case 1:return a.getAttribute("data-bind");case 8:return b.e.jb(a);default:return p}},parseBindingsString:function(a,d,c){try{var e;if(!(e=this.Ha[a])){var f=this.Ha,g,h="with($context){with($data||{}){return{"+b.g.ba(a)+"}}}";g=new Function("$context","$element",h);e=f[a]=g}return e(d,c)}catch(k){j(Error("Unable to parse bindings.\nMessage: "+
	k+";\nBindings value: "+a))}}});b.J.instance=new b.J;b.b("bindingProvider",b.J);b.c={};b.z=function(a,d,c){d?(b.a.extend(this,d),this.$parentContext=d,this.$parent=d.$data,this.$parents=(d.$parents||[]).slice(0),this.$parents.unshift(this.$parent)):(this.$parents=[],this.$root=a,this.ko=b);this.$data=a;c&&(this[c]=a)};b.z.prototype.createChildContext=function(a,d){return new b.z(a,this,d)};b.z.prototype.extend=function(a){var d=b.a.extend(new b.z,this);return b.a.extend(d,a)};b.eb=function(a,d){if(2==
	arguments.length)b.a.f.set(a,"__ko_bindingContext__",d);else return b.a.f.get(a,"__ko_bindingContext__")};b.Fa=function(a,d,c){1===a.nodeType&&b.e.Ta(a);return X(a,d,c,m)};b.Ea=function(a,b){(1===b.nodeType||8===b.nodeType)&&Z(a,b,m)};b.Da=function(a,b){b&&(1!==b.nodeType&&8!==b.nodeType)&&j(Error("ko.applyBindings: first parameter should be your view model; second parameter should be a DOM node"));b=b||x.document.body;Y(a,b,m)};b.ja=function(a){switch(a.nodeType){case 1:case 8:var d=b.eb(a);if(d)return d;
	if(a.parentNode)return b.ja(a.parentNode)}return I};b.pb=function(a){return(a=b.ja(a))?a.$data:I};b.b("bindingHandlers",b.c);b.b("applyBindings",b.Da);b.b("applyBindingsToDescendants",b.Ea);b.b("applyBindingsToNode",b.Fa);b.b("contextFor",b.ja);b.b("dataFor",b.pb);var fa={"class":"className","for":"htmlFor"};b.c.attr={update:function(a,d){var c=b.a.d(d())||{},e;for(e in c)if("string"==typeof e){var f=b.a.d(c[e]),g=f===r||f===p||f===I;g&&a.removeAttribute(e);8>=b.a.Z&&e in fa?(e=fa[e],g?a.removeAttribute(e):
	a[e]=f):g||a.setAttribute(e,f.toString());"name"===e&&b.a.ab(a,g?"":f.toString())}}};b.c.checked={init:function(a,d,c){b.a.n(a,"click",function(){var e;if("checkbox"==a.type)e=a.checked;else if("radio"==a.type&&a.checked)e=a.value;else return;var f=d(),g=b.a.d(f);"checkbox"==a.type&&g instanceof Array?(e=b.a.i(g,a.value),a.checked&&0>e?f.push(a.value):!a.checked&&0<=e&&f.splice(e,1)):b.g.ea(f,c,"checked",e,m)});"radio"==a.type&&!a.name&&b.c.uniqueName.init(a,u(m))},update:function(a,d){var c=b.a.d(d());
	"checkbox"==a.type?a.checked=c instanceof Array?0<=b.a.i(c,a.value):c:"radio"==a.type&&(a.checked=a.value==c)}};b.c.css={update:function(a,d){var c=b.a.d(d());if("object"==typeof c)for(var e in c){var f=b.a.d(c[e]);b.a.da(a,e,f)}else c=String(c||""),b.a.da(a,a.__ko__cssValue,r),a.__ko__cssValue=c,b.a.da(a,c,m)}};b.c.enable={update:function(a,d){var c=b.a.d(d());c&&a.disabled?a.removeAttribute("disabled"):!c&&!a.disabled&&(a.disabled=m)}};b.c.disable={update:function(a,d){b.c.enable.update(a,function(){return!b.a.d(d())})}};
	b.c.event={init:function(a,d,c,e){var f=d()||{},g;for(g in f)(function(){var f=g;"string"==typeof f&&b.a.n(a,f,function(a){var g,n=d()[f];if(n){var q=c();try{var s=b.a.L(arguments);s.unshift(e);g=n.apply(e,s)}finally{g!==m&&(a.preventDefault?a.preventDefault():a.returnValue=r)}q[f+"Bubble"]===r&&(a.cancelBubble=m,a.stopPropagation&&a.stopPropagation())}})})()}};b.c.foreach={Sa:function(a){return function(){var d=a(),c=b.a.ua(d);if(!c||"number"==typeof c.length)return{foreach:d,templateEngine:b.C.oa};
	b.a.d(d);return{foreach:c.data,as:c.as,includeDestroyed:c.includeDestroyed,afterAdd:c.afterAdd,beforeRemove:c.beforeRemove,afterRender:c.afterRender,beforeMove:c.beforeMove,afterMove:c.afterMove,templateEngine:b.C.oa}}},init:function(a,d){return b.c.template.init(a,b.c.foreach.Sa(d))},update:function(a,d,c,e,f){return b.c.template.update(a,b.c.foreach.Sa(d),c,e,f)}};b.g.Q.foreach=r;b.e.I.foreach=m;b.c.hasfocus={init:function(a,d,c){function e(e){a.__ko_hasfocusUpdating=m;var f=a.ownerDocument;"activeElement"in
	f&&(e=f.activeElement===a);f=d();b.g.ea(f,c,"hasfocus",e,m);a.__ko_hasfocusUpdating=r}var f=e.bind(p,m),g=e.bind(p,r);b.a.n(a,"focus",f);b.a.n(a,"focusin",f);b.a.n(a,"blur",g);b.a.n(a,"focusout",g)},update:function(a,d){var c=b.a.d(d());a.__ko_hasfocusUpdating||(c?a.focus():a.blur(),b.r.K(b.a.Ba,p,[a,c?"focusin":"focusout"]))}};b.c.html={init:function(){return{controlsDescendantBindings:m}},update:function(a,d){b.a.ca(a,d())}};var da="__ko_withIfBindingData";Q("if");Q("ifnot",r,m);Q("with",m,r,function(a,
	b){return a.createChildContext(b)});b.c.options={update:function(a,d,c){"select"!==b.a.u(a)&&j(Error("options binding applies only to SELECT elements"));for(var e=0==a.length,f=b.a.V(b.a.fa(a.childNodes,function(a){return a.tagName&&"option"===b.a.u(a)&&a.selected}),function(a){return b.k.q(a)||a.innerText||a.textContent}),g=a.scrollTop,h=b.a.d(d());0<a.length;)b.A(a.options[0]),a.remove(0);if(h){c=c();var k=c.optionsIncludeDestroyed;"number"!=typeof h.length&&(h=[h]);if(c.optionsCaption){var l=y.createElement("option");
	b.a.ca(l,c.optionsCaption);b.k.T(l,I);a.appendChild(l)}d=0;for(var n=h.length;d<n;d++){var q=h[d];if(!q||!q._destroy||k){var l=y.createElement("option"),s=function(a,b,c){var d=typeof b;return"function"==d?b(a):"string"==d?a[b]:c},v=s(q,c.optionsValue,q);b.k.T(l,b.a.d(v));q=s(q,c.optionsText,v);b.a.cb(l,q);a.appendChild(l)}}h=a.getElementsByTagName("option");d=k=0;for(n=h.length;d<n;d++)0<=b.a.i(f,b.k.q(h[d]))&&(b.a.bb(h[d],m),k++);a.scrollTop=g;e&&"value"in c&&ea(a,b.a.ua(c.value),m);b.a.ub(a)}}};
	b.c.options.sa="__ko.optionValueDomData__";b.c.selectedOptions={init:function(a,d,c){b.a.n(a,"change",function(){var e=d(),f=[];b.a.o(a.getElementsByTagName("option"),function(a){a.selected&&f.push(b.k.q(a))});b.g.ea(e,c,"value",f)})},update:function(a,d){"select"!=b.a.u(a)&&j(Error("values binding applies only to SELECT elements"));var c=b.a.d(d());c&&"number"==typeof c.length&&b.a.o(a.getElementsByTagName("option"),function(a){var d=0<=b.a.i(c,b.k.q(a));b.a.bb(a,d)})}};b.c.style={update:function(a,
	d){var c=b.a.d(d()||{}),e;for(e in c)if("string"==typeof e){var f=b.a.d(c[e]);a.style[e]=f||""}}};b.c.submit={init:function(a,d,c,e){"function"!=typeof d()&&j(Error("The value for a submit binding must be a function"));b.a.n(a,"submit",function(b){var c,h=d();try{c=h.call(e,a)}finally{c!==m&&(b.preventDefault?b.preventDefault():b.returnValue=r)}})}};b.c.text={update:function(a,d){b.a.cb(a,d())}};b.e.I.text=m;b.c.uniqueName={init:function(a,d){if(d()){var c="ko_unique_"+ ++b.c.uniqueName.ob;b.a.ab(a,
	c)}}};b.c.uniqueName.ob=0;b.c.value={init:function(a,d,c){function e(){h=r;var e=d(),f=b.k.q(a);b.g.ea(e,c,"value",f)}var f=["change"],g=c().valueUpdate,h=r;g&&("string"==typeof g&&(g=[g]),b.a.P(f,g),f=b.a.Ga(f));if(b.a.Z&&("input"==a.tagName.toLowerCase()&&"text"==a.type&&"off"!=a.autocomplete&&(!a.form||"off"!=a.form.autocomplete))&&-1==b.a.i(f,"propertychange"))b.a.n(a,"propertychange",function(){h=m}),b.a.n(a,"blur",function(){h&&e()});b.a.o(f,function(c){var d=e;b.a.Ob(c,"after")&&(d=function(){setTimeout(e,
	0)},c=c.substring(5));b.a.n(a,c,d)})},update:function(a,d){var c="select"===b.a.u(a),e=b.a.d(d()),f=b.k.q(a),g=e!=f;0===e&&(0!==f&&"0"!==f)&&(g=m);g&&(f=function(){b.k.T(a,e)},f(),c&&setTimeout(f,0));c&&0<a.length&&ea(a,e,r)}};b.c.visible={update:function(a,d){var c=b.a.d(d()),e="none"!=a.style.display;c&&!e?a.style.display="":!c&&e&&(a.style.display="none")}};b.c.click={init:function(a,d,c,e){return b.c.event.init.call(this,a,function(){var a={};a.click=d();return a},c,e)}};b.v=function(){};b.v.prototype.renderTemplateSource=
	function(){j(Error("Override renderTemplateSource"))};b.v.prototype.createJavaScriptEvaluatorBlock=function(){j(Error("Override createJavaScriptEvaluatorBlock"))};b.v.prototype.makeTemplateSource=function(a,d){if("string"==typeof a){d=d||y;var c=d.getElementById(a);c||j(Error("Cannot find template with ID "+a));return new b.l.h(c)}if(1==a.nodeType||8==a.nodeType)return new b.l.O(a);j(Error("Unknown template type: "+a))};b.v.prototype.renderTemplate=function(a,b,c,e){a=this.makeTemplateSource(a,e);
	return this.renderTemplateSource(a,b,c)};b.v.prototype.isTemplateRewritten=function(a,b){return this.allowTemplateRewriting===r?m:this.makeTemplateSource(a,b).data("isRewritten")};b.v.prototype.rewriteTemplate=function(a,b,c){a=this.makeTemplateSource(a,c);b=b(a.text());a.text(b);a.data("isRewritten",m)};b.b("templateEngine",b.v);var qa=/(<[a-z]+\d*(\s+(?!data-bind=)[a-z0-9\-]+(=(\"[^\"]*\"|\'[^\']*\'))?)*\s+)data-bind=(["'])([\s\S]*?)\5/gi,ra=/\x3c!--\s*ko\b\s*([\s\S]*?)\s*--\x3e/g;b.za={vb:function(a,
	d,c){d.isTemplateRewritten(a,c)||d.rewriteTemplate(a,function(a){return b.za.Gb(a,d)},c)},Gb:function(a,b){return a.replace(qa,function(a,e,f,g,h,k,l){return W(l,e,b)}).replace(ra,function(a,e){return W(e,"\x3c!-- ko --\x3e",b)})},kb:function(a){return b.s.ra(function(d,c){d.nextSibling&&b.Fa(d.nextSibling,a,c)})}};b.b("__tr_ambtns",b.za.kb);b.l={};b.l.h=function(a){this.h=a};b.l.h.prototype.text=function(){var a=b.a.u(this.h),a="script"===a?"text":"textarea"===a?"value":"innerHTML";if(0==arguments.length)return this.h[a];
	var d=arguments[0];"innerHTML"===a?b.a.ca(this.h,d):this.h[a]=d};b.l.h.prototype.data=function(a){if(1===arguments.length)return b.a.f.get(this.h,"templateSourceData_"+a);b.a.f.set(this.h,"templateSourceData_"+a,arguments[1])};b.l.O=function(a){this.h=a};b.l.O.prototype=new b.l.h;b.l.O.prototype.text=function(){if(0==arguments.length){var a=b.a.f.get(this.h,"__ko_anon_template__")||{};a.Aa===I&&a.ia&&(a.Aa=a.ia.innerHTML);return a.Aa}b.a.f.set(this.h,"__ko_anon_template__",{Aa:arguments[0]})};b.l.h.prototype.nodes=
	function(){if(0==arguments.length)return(b.a.f.get(this.h,"__ko_anon_template__")||{}).ia;b.a.f.set(this.h,"__ko_anon_template__",{ia:arguments[0]})};b.b("templateSources",b.l);b.b("templateSources.domElement",b.l.h);b.b("templateSources.anonymousTemplate",b.l.O);var O;b.wa=function(a){a!=I&&!(a instanceof b.v)&&j(Error("templateEngine must inherit from ko.templateEngine"));O=a};b.va=function(a,d,c,e,f){c=c||{};(c.templateEngine||O)==I&&j(Error("Set a template engine before calling renderTemplate"));
	f=f||"replaceChildren";if(e){var g=N(e);return b.j(function(){var h=d&&d instanceof b.z?d:new b.z(b.a.d(d)),k="function"==typeof a?a(h.$data,h):a,h=T(e,f,k,h,c);"replaceNode"==f&&(e=h,g=N(e))},p,{Ka:function(){return!g||!b.a.X(g)},W:g&&"replaceNode"==f?g.parentNode:g})}return b.s.ra(function(e){b.va(a,d,c,e,"replaceNode")})};b.Mb=function(a,d,c,e,f){function g(a,b){U(b,k);c.afterRender&&c.afterRender(b,a)}function h(d,e){k=f.createChildContext(b.a.d(d),c.as);k.$index=e;var g="function"==typeof a?
	a(d,k):a;return T(p,"ignoreTargetNode",g,k,c)}var k;return b.j(function(){var a=b.a.d(d)||[];"undefined"==typeof a.length&&(a=[a]);a=b.a.fa(a,function(a){return c.includeDestroyed||a===I||a===p||!b.a.d(a._destroy)});b.r.K(b.a.$a,p,[e,a,h,c,g])},p,{W:e})};b.c.template={init:function(a,d){var c=b.a.d(d());if("string"!=typeof c&&!c.name&&(1==a.nodeType||8==a.nodeType))c=1==a.nodeType?a.childNodes:b.e.childNodes(a),c=b.a.Hb(c),(new b.l.O(a)).nodes(c);return{controlsDescendantBindings:m}},update:function(a,
	d,c,e,f){d=b.a.d(d());c={};e=m;var g,h=p;"string"!=typeof d&&(c=d,d=c.name,"if"in c&&(e=b.a.d(c["if"])),e&&"ifnot"in c&&(e=!b.a.d(c.ifnot)),g=b.a.d(c.data));"foreach"in c?h=b.Mb(d||a,e&&c.foreach||[],c,a,f):e?(f="data"in c?f.createChildContext(g,c.as):f,h=b.va(d||a,f,c,a)):b.e.Y(a);f=h;(g=b.a.f.get(a,"__ko__templateComputedDomDataKey__"))&&"function"==typeof g.B&&g.B();b.a.f.set(a,"__ko__templateComputedDomDataKey__",f&&f.pa()?f:I)}};b.g.Q.template=function(a){a=b.g.aa(a);return 1==a.length&&a[0].unknown||
	b.g.Eb(a,"name")?p:"This template engine does not support anonymous templates nested within its templates"};b.e.I.template=m;b.b("setTemplateEngine",b.wa);b.b("renderTemplate",b.va);b.a.Ja=function(a,b,c){a=a||[];b=b||[];return a.length<=b.length?S(a,b,"added","deleted",c):S(b,a,"deleted","added",c)};b.b("utils.compareArrays",b.a.Ja);b.a.$a=function(a,d,c,e,f){function g(a,b){t=l[b];w!==b&&(z[a]=t);t.na(w++);M(t.M);s.push(t);A.push(t)}function h(a,c){if(a)for(var d=0,e=c.length;d<e;d++)c[d]&&b.a.o(c[d].M,
	function(b){a(b,d,c[d].U)})}d=d||[];e=e||{};var k=b.a.f.get(a,"setDomNodeChildrenFromArrayMapping_lastMappingResult")===I,l=b.a.f.get(a,"setDomNodeChildrenFromArrayMapping_lastMappingResult")||[],n=b.a.V(l,function(a){return a.U}),q=b.a.Ja(n,d),s=[],v=0,w=0,B=[],A=[];d=[];for(var z=[],n=[],t,D=0,C,E;C=q[D];D++)switch(E=C.moved,C.status){case "deleted":E===I&&(t=l[v],t.j&&t.j.B(),B.push.apply(B,M(t.M)),e.beforeRemove&&(d[D]=t,A.push(t)));v++;break;case "retained":g(D,v++);break;case "added":E!==I?
	g(D,E):(t={U:C.value,na:b.m(w++)},s.push(t),A.push(t),k||(n[D]=t))}h(e.beforeMove,z);b.a.o(B,e.beforeRemove?b.A:b.removeNode);for(var D=0,k=b.e.firstChild(a),H;t=A[D];D++){t.M||b.a.extend(t,ha(a,c,t.U,f,t.na));for(v=0;q=t.M[v];k=q.nextSibling,H=q,v++)q!==k&&b.e.Pa(a,q,H);!t.Ab&&f&&(f(t.U,t.M,t.na),t.Ab=m)}h(e.beforeRemove,d);h(e.afterMove,z);h(e.afterAdd,n);b.a.f.set(a,"setDomNodeChildrenFromArrayMapping_lastMappingResult",s)};b.b("utils.setDomNodeChildrenFromArrayMapping",b.a.$a);b.C=function(){this.allowTemplateRewriting=
	r};b.C.prototype=new b.v;b.C.prototype.renderTemplateSource=function(a){var d=!(9>b.a.Z)&&a.nodes?a.nodes():p;if(d)return b.a.L(d.cloneNode(m).childNodes);a=a.text();return b.a.ta(a)};b.C.oa=new b.C;b.wa(b.C.oa);b.b("nativeTemplateEngine",b.C);b.qa=function(){var a=this.Db=function(){if("undefined"==typeof F||!F.tmpl)return 0;try{if(0<=F.tmpl.tag.tmpl.open.toString().indexOf("__"))return 2}catch(a){}return 1}();this.renderTemplateSource=function(b,c,e){e=e||{};2>a&&j(Error("Your version of jQuery.tmpl is too old. Please upgrade to jQuery.tmpl 1.0.0pre or later."));
	var f=b.data("precompiled");f||(f=b.text()||"",f=F.template(p,"{{ko_with $item.koBindingContext}}"+f+"{{/ko_with}}"),b.data("precompiled",f));b=[c.$data];c=F.extend({koBindingContext:c},e.templateOptions);c=F.tmpl(f,b,c);c.appendTo(y.createElement("div"));F.fragments={};return c};this.createJavaScriptEvaluatorBlock=function(a){return"{{ko_code ((function() { return "+a+" })()) }}"};this.addTemplate=function(a,b){y.write("<script type='text/html' id='"+a+"'>"+b+"\x3c/script>")};0<a&&(F.tmpl.tag.ko_code=
	{open:"__.push($1 || '');"},F.tmpl.tag.ko_with={open:"with($1) {",close:"} "})};b.qa.prototype=new b.v;w=new b.qa;0<w.Db&&b.wa(w);b.b("jqueryTmplTemplateEngine",b.qa)}"function"===typeof require&&"object"===typeof exports&&"object"===typeof module?L(module.exports||exports):"function"===typeof define&&define.amd?define(["exports"],L):L(x.ko={});m;
	})();
	</script>
<?php endif; ?>
	
<script type="text/javascript">
/* json2.js 
 * See www.JSON.org/js.html*/
if(!this.JSON){JSON=function(){function f(n){return n<10?'0'+n:n;}
Date.prototype.toJSON=function(){return this.getUTCFullYear()+'-'+
f(this.getUTCMonth()+1)+'-'+
f(this.getUTCDate())+'T'+
f(this.getUTCHours())+':'+
f(this.getUTCMinutes())+':'+
f(this.getUTCSeconds())+'Z';};var m={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'};function stringify(value,whitelist){var a,i,k,l,r=/["\\\x00-\x1f\x7f-\x9f]/g,v;switch(typeof value){case'string':return r.test(value)?'"'+value.replace(r,function(a){var c=m[a];if(c){return c;}
c=a.charCodeAt();return'\\u00'+Math.floor(c/16).toString(16)+
(c%16).toString(16);})+'"':'"'+value+'"';case'number':return isFinite(value)?String(value):'null';case'boolean':case'null':return String(value);case'object':if(!value){return'null';}
if(typeof value.toJSON==='function'){return stringify(value.toJSON());}
a=[];if(typeof value.length==='number'&&!(value.propertyIsEnumerable('length'))){l=value.length;for(i=0;i<l;i+=1){a.push(stringify(value[i],whitelist)||'null');}
return'['+a.join(',')+']';}
if(whitelist){l=whitelist.length;for(i=0;i<l;i+=1){k=whitelist[i];if(typeof k==='string'){v=stringify(value[k],whitelist);if(v){a.push(stringify(k)+':'+v);}}}}else{for(k in value){if(typeof k==='string'){v=stringify(value[k],whitelist);if(v){a.push(stringify(k)+':'+v);}}}}
return'{'+a.join(',')+'}';}}
return{stringify:stringify,parse:function(text,filter){var j;function walk(k,v){var i,n;if(v&&typeof v==='object'){for(i in v){if(Object.prototype.hasOwnProperty.apply(v,[i])){n=walk(i,v[i]);if(n!==undefined){v[i]=n;}}}}
return filter(k,v);}
if(/^[\],:{}\s]*$/.test(text.replace(/\\./g,'@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,']').replace(/(?:^|:|,)(?:\s*\[)+/g,''))){j=eval('('+text+')');return typeof filter==='function'?walk('',j):j;}
throw new SyntaxError('parseJSON');}};}();}
</script>

<script type="text/javascript">
/**
 * password_strength_plugin.js
 * Copyright (c) 20010 myPocket technologies (www.mypocket-technologies.com)
 * @author Darren Mason (djmason9@gmail.com)
 * @date 3/13/2009
 * @projectDescription Password Strength Meter is a jQuery plug-in provide you smart algorithm to detect a password strength. 
 * Based on Firas Kassem orginal plugin - phiras.wordpress.com/2007/04/08/password-strength-meter-a-jquery-plugin/
 * @version 1.0.1	*/
(function(a){a.fn.shortPass="Too short";a.fn.badPass="Weak";a.fn.goodPass="Good";a.fn.strongPass="Strong";a.fn.samePassword="Username and Password identical.";a.fn.resultStyle="";a.fn.passStrength=function(b){var d={shortPass:"shortPass",badPass:"badPass",goodPass:"goodPass",strongPass:"strongPass",baseStyle:"testresult",userid:"",messageloc:1};
var c=a.extend(d,b);return this.each(function(){var e=a(this);a(e).unbind().keyup(function(){var f=a.fn.teststrength(a(this).val(),a(c.userid).val(),c);if(c.messageloc===1){a(this).next("."+c.baseStyle).remove();a(this).after('<span class="'+c.baseStyle+'"><span></span></span>');a(this).next("."+c.baseStyle).addClass(a(this).resultStyle).find("span").text(f)
}else{a(this).prev("."+c.baseStyle).remove();a(this).before('<span class="'+c.baseStyle+'"><span></span></span>');a(this).prev("."+c.baseStyle).addClass(a(this).resultStyle).find("span").text(f)}});a.fn.teststrength=function(f,i,g){var h=0;if(f.length<4){this.resultStyle=g.shortPass;return a(this).shortPass
}if(f.toLowerCase()==i.toLowerCase()){this.resultStyle=g.badPass;return a(this).samePassword}h+=f.length*4;h+=(a.fn.checkRepetition(1,f).length-f.length)*1;h+=(a.fn.checkRepetition(2,f).length-f.length)*1;h+=(a.fn.checkRepetition(3,f).length-f.length)*1;h+=(a.fn.checkRepetition(4,f).length-f.length)*1;
if(f.match(/(.*[0-9].*[0-9].*[0-9])/)){h+=5}if(f.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/)){h+=5}if(f.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)){h+=10}if(f.match(/([a-zA-Z])/)&&f.match(/([0-9])/)){h+=15}if(f.match(/([!,@,#,$,%,^,&,*,?,_,~])/)&&f.match(/([0-9])/)){h+=15}if(f.match(/([!,@,#,$,%,^,&,*,?,_,~])/)&&f.match(/([a-zA-Z])/)){h+=15
}if(f.match(/^\w+$/)||f.match(/^\d+$/)){h-=10}if(h<0){h=0}if(h>100){h=100}if(h<34){this.resultStyle=g.badPass;return a(this).badPass}if(h<68){this.resultStyle=g.goodPass;return a(this).goodPass}this.resultStyle=g.strongPass;return a(this).strongPass}})}})(jQuery);$.fn.checkRepetition=function(a,f){var d="";
for(var c=0;c<f.length;c++){var e=true;for(var b=0;b<a&&(b+c+a)<f.length;b++){e=e&&(f.charAt(b+c)==f.charAt(b+c+a))}if(b<a){e=false}if(e){c+=a-1;e=false}else{d+=f.charAt(c)}}return d};
</script>


<script type="text/javascript">
/* Parsley dist/parsley.min.js build version 1.2.2 http://parsleyjs.org */
!function(d){var h=function(a){this.messages={defaultMessage:"This value seems to be invalid.",type:{email:"This value should be a valid email.",url:"This value should be a valid url.",urlstrict:"This value should be a valid url.",number:"This value should be a valid number.",digits:"This value should be digits.",dateIso:"This value should be a valid date (YYYY-MM-DD).",alphanum:"This value should be alphanumeric.",phone:"This value should be a valid phone number."},notnull:"This value should not be null.",
notblank:"This value should not be blank.",required:"This value is required.",regexp:"This value seems to be invalid.",min:"This value should be greater than or equal to %s.",max:"This value should be lower than or equal to %s.",range:"This value should be between %s and %s.",minlength:"This value is too short. It should have %s characters or more.",maxlength:"This value is too long. It should have %s characters or less.",rangelength:"This value length is invalid. It should be between %s and %s characters long.",
mincheck:"You must select at least %s choices.",maxcheck:"You must select %s choices or less.",rangecheck:"You must select between %s and %s choices.",equalto:"This value should be the same."};this.init(a)};h.prototype={constructor:h,validators:{notnull:function(){return{validate:function(a){return 0<a.length},priority:2}},notblank:function(){return{validate:function(a){return"string"===typeof a&&""!==a.replace(/^\s+/g,"").replace(/\s+$/g,"")},priority:2}},required:function(){var a=this;return{validate:function(b){if("object"===
typeof b){for(var c in b)if(a.required().validate(b[c]))return!0;return!1}return a.notnull().validate(b)&&a.notblank().validate(b)},priority:512}},type:function(){return{validate:function(a,b){var c;switch(b){case "number":c=/^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/;break;case "digits":c=/^\d+$/;break;case "alphanum":c=/^\w+$/;break;case "email":c=/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))){2,6}$/i;
break;case "url":a=/(https?|s?ftp|git)/i.test(a)?a:"http://"+a;case "urlstrict":c=/^(https?|s?ftp|git):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i;
break;case "dateIso":c=/^(\d{4})\D?(0[1-9]|1[0-2])\D?([12]\d|0[1-9]|3[01])$/;break;case "phone":c=/^((\+\d{1,3}(-| )?\(?\d\)?(-| )?\d{1,5})|(\(?\d{2,6}\)?))(-| )?(\d{3,4})(-| )?(\d{4})(( x| ext)\d{1,5}){0,1}$/;break;default:return!1}return""!==a?c.test(a):!1},priority:256}},regexp:function(){return{validate:function(a,b,c){return RegExp(b,c.options.regexpFlag||"").test(a)},priority:64}},minlength:function(){return{validate:function(a,b){return a.length>=b},priority:32}},maxlength:function(){return{validate:function(a,
b){return a.length<=b},priority:32}},rangelength:function(){var a=this;return{validate:function(b,c){return a.minlength().validate(b,c[0])&&a.maxlength().validate(b,c[1])},priority:32}},min:function(){return{validate:function(a,b){return Number(a)>=b},priority:32}},max:function(){return{validate:function(a,b){return Number(a)<=b},priority:32}},range:function(){var a=this;return{validate:function(b,c){return a.min().validate(b,c[0])&&a.max().validate(b,c[1])},priority:32}},equalto:function(){return{validate:function(a,
b,c){c.options.validateIfUnchanged=!0;return a===d(b).val()},priority:64}},remote:function(){return{validate:function(a,b,c){var e={},f={};e[c.$element.attr("name")]=a;"undefined"!==typeof c.options.remoteDatatype&&(f={dataType:c.options.remoteDatatype});var g=function(a,b){"undefined"!==typeof b&&("undefined"!==typeof c.Validator.messages.remote&&b!==c.Validator.messages.remote)&&d(c.UI.ulError+" .remote").remove();if(!1===a)c.options.listeners.onFieldError(c.element,c.constraints,c);else!0===a&&
!1===c.options.listeners.onFieldSuccess(c.element,c.constraints,c)&&(a=!1);c.updtConstraint({name:"remote",valid:a},b);c.manageValidationResult()},k=function(a){if("object"===typeof a)return a;try{a=d.parseJSON(a)}catch(b){}return a},l=function(a){return"object"===typeof a&&null!==a?"undefined"!==typeof a.error?a.error:"undefined"!==typeof a.message?a.message:null:null};d.ajax(d.extend({},{url:b,data:e,type:c.options.remoteMethod||"GET",success:function(a){a=k(a);g(1===a||!0===a||"object"===typeof a&&
null!==a&&"undefined"!==typeof a.success,l(a))},error:function(a){a=k(a);g(!1,l(a))}},f));return null},priority:64}},mincheck:function(){var a=this;return{validate:function(b,c){return a.minlength().validate(b,c)},priority:32}},maxcheck:function(){var a=this;return{validate:function(b,c){return a.maxlength().validate(b,c)},priority:32}},rangecheck:function(){var a=this;return{validate:function(b,c){return a.rangelength().validate(b,c)},priority:32}}},init:function(a){var b=a.validators;a=a.messages;
for(var c in b)this.addValidator(c,b[c]);for(c in a)this.addMessage(c,a[c])},formatMesssage:function(a,b){if("object"===typeof b){for(var c in b)a=this.formatMesssage(a,b[c]);return a}return"string"===typeof a?a.replace(/%s/i,b):""},addValidator:function(a,b){if("undefined"===typeof b().validate)throw Error("Validator `"+a+"` must have a validate method. See more here: http://parsleyjs.org/documentation.html#javascript-general");"undefined"===typeof b().priority&&(b={validate:b().validate,priority:32},
window.console&&window.console.warn&&window.console.warn("Validator `"+a+"` should have a priority. Default priority 32 given"));this.validators[a]=b},addMessage:function(a,b,c){if("undefined"!==typeof c&&!0===c)this.messages.type[a]=b;else if("type"===a)for(var d in b)this.messages.type[d]=b[d];else this.messages[a]=b}};var n=function(a){this.init(a)};n.prototype={constructor:n,init:function(a){this.ParsleyInstance=a;this.hash=a.hash;this.options=this.ParsleyInstance.options;this.errorClassHandler=
this.options.errors.classHandler(this.ParsleyInstance.element,this.ParsleyInstance.isRadioOrCheckbox)||this.ParsleyInstance.$element;this.ulErrorManagement()},ulErrorManagement:function(){this.ulError="#"+this.hash;this.ulTemplate=d(this.options.errors.errorsWrapper).attr("id",this.hash).addClass("parsley-error-list")},removeError:function(a){a=this.ulError+" ."+a;var b=this;this.options.animate?d(a).fadeOut(this.options.animateDuration,function(){d(this).remove();b.ulError&&0===d(b.ulError).children().length&&
b.removeErrors()}):d(a).remove()},addError:function(a){for(var b in a){var c=d(this.options.errors.errorElem).addClass(b);d(this.ulError).append(this.options.animate?d(c).html(a[b]).hide().fadeIn(this.options.animateDuration):d(c).html(a[b]))}},removeErrors:function(){this.options.animate?d(this.ulError).fadeOut(this.options.animateDuration,function(){d(this).remove()}):d(this.ulError).remove()},reset:function(){this.ParsleyInstance.valid=null;this.removeErrors();this.ParsleyInstance.validatedOnce=
!1;this.errorClassHandler.removeClass(this.options.successClass).removeClass(this.options.errorClass);for(var a in this.constraints)this.constraints[a].valid=null;return this},manageError:function(a){d(this.ulError).length||this.manageErrorContainer();if(!("required"===a.name&&null!==this.ParsleyInstance.getVal()&&0<this.ParsleyInstance.getVal().length))if(this.ParsleyInstance.isRequired&&"required"!==a.name&&(null===this.ParsleyInstance.getVal()||0===this.ParsleyInstance.getVal().length))this.removeError(a.name);
else{var b=a.name,c=!1!==this.options.errorMessage?"custom-error-message":b,e={};a=!1!==this.options.errorMessage?this.options.errorMessage:"type"===a.name?this.ParsleyInstance.Validator.messages[b][a.requirements]:"undefined"===typeof this.ParsleyInstance.Validator.messages[b]?this.ParsleyInstance.Validator.messages.defaultMessage:this.ParsleyInstance.Validator.formatMesssage(this.ParsleyInstance.Validator.messages[b],a.requirements);d(this.ulError+" ."+c).length||(e[c]=a,this.addError(e))}},manageErrorContainer:function(){var a=
this.options.errorContainer||this.options.errors.container(this.ParsleyInstance.element,this.ParsleyInstance.isRadioOrCheckbox),b=this.options.animate?this.ulTemplate.css("display",""):this.ulTemplate;"undefined"!==typeof a?d(a).append(b):!this.ParsleyInstance.isRadioOrCheckbox?this.ParsleyInstance.$element.after(b):this.ParsleyInstance.$element.parent().after(b)}};var m=function(a,b,c){this.options=b;if("ParsleyFieldMultiple"===c)return this;this.init(a,c||"ParsleyField")};m.prototype={constructor:m,
init:function(a,b){this.type=b;this.valid=!0;this.element=a;this.validatedOnce=!1;this.$element=d(a);this.val=this.$element.val();this.Validator=new h(this.options);this.isRequired=!1;this.constraints={};"undefined"===typeof this.isRadioOrCheckbox&&(this.isRadioOrCheckbox=!1,this.hash=this.generateHash());this.UI=new n(this);this.bindHtml5Constraints();this.addConstraints();this.hasConstraints()&&this.bindValidationEvents()},setParent:function(a){this.$parent=d(a)},getParent:function(){return this.$parent},
bindHtml5Constraints:function(){if(this.$element.hasClass("required")||this.$element.attr("required"))this.options.required=!0;var a=this.$element.attr("type");"undefined"!==typeof a&&RegExp(a,"i").test("email url number range tel")&&(this.options.type="tel"===a?"phone":a,RegExp(this.options.type,"i").test("number range")&&(this.options.type="number","undefined"!==typeof this.$element.attr("min")&&this.$element.attr("min").length&&(this.options.min=this.$element.attr("min")),"undefined"!==typeof this.$element.attr("max")&&
this.$element.attr("max").length&&(this.options.max=this.$element.attr("max"))));"string"===typeof this.$element.attr("pattern")&&this.$element.attr("pattern").length&&(this.options.regexp=this.$element.attr("pattern"))},addConstraints:function(){for(var a in this.options){var b={};b[a]=this.options[a];this.addConstraint(b,!0,!1)}},addConstraint:function(a,b,c){for(var d in a)d=d.toLowerCase(),"function"===typeof this.Validator.validators[d]&&(this.constraints[d]={name:d,requirements:a[d],valid:null},
"required"===d&&(this.isRequired=!0),this.addCustomConstraintMessage(d));"undefined"===typeof b&&this.bindValidationEvents()},updateConstraint:function(a,b){for(var c in a)this.updtConstraint({name:c,requirements:a[c],valid:null},b)},updtConstraint:function(a,b){this.constraints[a.name]=d.extend(!0,this.constraints[a.name],a);"string"===typeof b&&(this.Validator.messages[a.name]=b);this.bindValidationEvents()},removeConstraint:function(a){a=a.toLowerCase();delete this.constraints[a];"required"===
a&&(this.isRequired=!1);this.hasConstraints()?this.bindValidationEvents():this.UI.reset()},addCustomConstraintMessage:function(a){var b=a+("type"===a&&"undefined"!==typeof this.options[a]?this.options[a].charAt(0).toUpperCase()+this.options[a].substr(1):"")+"Message";"undefined"!==typeof this.options[b]&&this.Validator.addMessage("type"===a?this.options[a]:a,this.options[b],"type"===a)},bindValidationEvents:function(){this.valid=null;this.$element.addClass("parsley-validated");this.$element.off("."+
this.type);this.options.remote&&!/change/i.test(this.options.trigger)&&(this.options.trigger=!this.options.trigger?"change":" change");var a=(!this.options.trigger?"":this.options.trigger)+(/key/i.test(this.options.trigger)?"":" keyup");this.$element.is("select")&&(a+=/change/i.test(a)?"":" change");a=a.replace(/^\s+/g,"").replace(/\s+$/g,"");this.$element.on((a+" ").split(" ").join("."+this.type+" "),!1,d.proxy(this.eventValidation,this))},generateHash:function(){return"parsley-"+(Math.random()+
"").substring(2)},getHash:function(){return this.hash},getVal:function(){return"undefined"!==typeof this.$element.domApi(this.options.namespace).value?this.$element.domApi(this.options.namespace).value:this.$element.val()},eventValidation:function(a){var b=this.getVal();if("keyup"===a.type&&!/keyup/i.test(this.options.trigger)&&!this.validatedOnce||"change"===a.type&&!/change/i.test(this.options.trigger)&&!this.validatedOnce||!this.isRadioOrCheckbox&&this.getLength(b)<this.options.validationMinlength&&
!this.validatedOnce)return!0;this.validate()},getLength:function(a){return!a||!a.hasOwnProperty("length")?0:a.length},isValid:function(){return this.validate(!1)},hasConstraints:function(){for(var a in this.constraints)return!0;return!1},validate:function(a){var b=this.getVal(),c=null;if(!this.hasConstraints()||this.$element.is(this.options.excluded))return null;if(this.options.listeners.onFieldValidate(this.element,this)||""===b&&!this.isRequired)return this.UI.reset(),null;if(!this.needsValidation(b))return this.valid;
c=this.applyValidators();("undefined"!==typeof a?a:this.options.showErrors)&&this.manageValidationResult();return c},needsValidation:function(a){if(!this.options.validateIfUnchanged&&null!==this.valid&&this.val===a&&this.validatedOnce)return!1;this.val=a;return this.validatedOnce=!0},applyValidators:function(){var a=null,b;for(b in this.constraints){var c=this.Validator.validators[this.constraints[b].name]().validate(this.val,this.constraints[b].requirements,this);!1===c?(a=!1,this.constraints[b].valid=
a):!0===c&&(this.constraints[b].valid=!0,a=!1!==a)}if(!1===a)this.options.listeners.onFieldError(this.element,this.constraints,this);else!0===a&&!1===this.options.listeners.onFieldSuccess(this.element,this.constraints,this)&&(a=!1);return a},manageValidationResult:function(){var a=null,b=[],c;for(c in this.constraints)!1===this.constraints[c].valid?(b.push(this.constraints[c]),a=!1):!0===this.constraints[c].valid&&(this.UI.removeError(this.constraints[c].name),a=!1!==a);this.valid=a;if(!0===this.valid)return this.UI.removeErrors(),
this.UI.errorClassHandler.removeClass(this.options.errorClass).addClass(this.options.successClass),!0;if(!1===this.valid){if(!0===this.options.priorityEnabled){for(var a=0,e,f=0;f<b.length;f++)e=this.Validator.validators[b[f].name]().priority,e>a&&(c=b[f],a=e);this.UI.manageError(c)}else for(f=0;f<b.length;f++)this.UI.manageError(b[f]);this.UI.errorClassHandler.removeClass(this.options.successClass).addClass(this.options.errorClass);return!1}this.UI.ulError&&0===d(this.ulError).children().length&&
this.UI.removeErrors();return a},addListener:function(a){for(var b in a)this.options.listeners[b]=a[b]},destroy:function(){this.$element.removeClass("parsley-validated");this.UI.reset();this.$element.off("."+this.type).removeData(this.type)}};var p=function(a,b,c){this.initMultiple(a,b);this.inherit(a,b);this.Validator=new h(b);this.init(a,c||"ParsleyFieldMultiple")};p.prototype={constructor:p,initMultiple:function(a,b){this.element=a;this.$element=d(a);this.group=b.group||!1;this.hash=this.getName();
this.siblings=this.group?'[parsley-group="'+this.group+'"]':'input[name="'+this.$element.attr("name")+'"]';this.isRadioOrCheckbox=!0;this.isRadio=this.$element.is("input[type=radio]");this.isCheckbox=this.$element.is("input[type=checkbox]");this.errorClassHandler=b.errors.classHandler(a,this.isRadioOrCheckbox)||this.$element.parent()},inherit:function(a,b){var c=new m(a,b,"ParsleyFieldMultiple"),d;for(d in c)"undefined"===typeof this[d]&&(this[d]=c[d])},getName:function(){if(this.group)return"parsley-"+
this.group;if("undefined"===typeof this.$element.attr("name"))throw"A radio / checkbox input must have a parsley-group attribute or a name to be Parsley validated !";return"parsley-"+this.$element.attr("name").replace(/(:|\.|\[|\])/g,"")},getVal:function(){if(this.isRadio)return d(this.siblings+":checked").val()||"";if(this.isCheckbox){var a=[];d(this.siblings+":checked").each(function(){a.push(d(this).val())});return a}},bindValidationEvents:function(){this.valid=null;this.$element.addClass("parsley-validated");
this.$element.off("."+this.type);var a=this,b=(!this.options.trigger?"":this.options.trigger)+(/change/i.test(this.options.trigger)?"":" change"),b=b.replace(/^\s+/g,"").replace(/\s+$/g,"");d(this.siblings).each(function(){d(this).on(b.split(" ").join("."+a.type+" "),!1,d.proxy(a.eventValidation,a))})}};var q=function(a,b,c){this.init(a,b,c||"parsleyForm")};q.prototype={constructor:q,init:function(a,b,c){this.type=c;this.items=[];this.$element=d(a);this.options=b;var e=this;this.$element.find(b.inputs).each(function(){e.addItem(this)});
this.$element.on("submit."+this.type,!1,d.proxy(this.validate,this))},addListener:function(a){for(var b in a)if(/Field/.test(b))for(var c=0;c<this.items.length;c++)this.items[c].addListener(a);else this.options.listeners[b]=a[b]},addItem:function(a){if(d(a).is(this.options.excluded))return!1;a=d(a).parsley(this.options);a.setParent(this);this.items.push(a)},removeItem:function(a){a=d(a).parsley();for(var b=0;b<this.items.length;b++)if(this.items[b].hash===a.hash)return this.items[b].destroy(),this.items.splice(b,
1),!0;return!1},validate:function(a){var b=!0;this.focusedField=!1;for(var c=0;c<this.items.length;c++)if("undefined"!==typeof this.items[c]&&!1===this.items[c].validate()&&(b=!1,!this.focusedField&&"first"===this.options.focus||"last"===this.options.focus))this.focusedField=this.items[c].$element;if(this.focusedField&&!b)if(0<this.options.scrollDuration){var e=this,c=this.focusedField.offset().top-d(window).height()/2;d("html, body").animate({scrollTop:c},this.options.scrollDuration,function(){e.focusedField.focus()})}else this.focusedField.focus();
a=this.options.listeners.onFormValidate(b,a,this);return"undefined"!==typeof a?a:b},isValid:function(){for(var a=0;a<this.items.length;a++)if(!1===this.items[a].isValid())return!1;return!0},removeErrors:function(){for(var a=0;a<this.items.length;a++)this.items[a].parsley("reset")},destroy:function(){for(var a=0;a<this.items.length;a++)this.items[a].destroy();this.$element.off("."+this.type).removeData(this.type)},reset:function(){for(var a=0;a<this.items.length;a++)this.items[a].UI.reset()}};d.fn.parsley=
function(a,b){function c(b,c){var e=d(b).data(c);if(!e){switch(c){case "parsleyForm":e=new q(b,f,"parsleyForm");break;case "parsleyField":e=new m(b,f,"parsleyField");break;case "parsleyFieldMultiple":e=new p(b,f,"parsleyFieldMultiple");break;default:return}d(b).data(c,e)}return"string"===typeof a&&"function"===typeof e[a]?(e=e[a].apply(e,k),"undefined"!==typeof e?e:d(b)):e}var e=d(this).data("parsleyNamespace")?d(this).data("parsleyNamespace"):"undefined"!==typeof a&&"undefined"!==typeof a.namespace?
a.namespace:d.fn.parsley.defaults.namespace,f=d.extend(!0,{},d.fn.parsley.defaults,"undefined"!==typeof window.ParsleyConfig?window.ParsleyConfig:{},a,this.domApi(e)),g=null,k=Array.prototype.slice.call(arguments,1);d(this).is("form")||"undefined"!==typeof d(this).domApi(e).bind?g=c(d(this),"parsleyForm"):d(this).is(f.inputs)&&(g=c(d(this),!d(this).is("input[type=radio], input[type=checkbox]")?"parsleyField":"parsleyFieldMultiple"));return"function"===typeof b?b():g};d(window).on("load",function(){d("[parsley-validate], [data-parsley-validate]").each(function(){d(this).parsley()})});
d.fn.domApi=function(a){var b,c={},e=RegExp("^"+a,"i");if("undefined"===typeof this[0])return{};for(var f in this[0].attributes)if(b=this[0].attributes[f],null!==b&&b.specified&&e.test(b.name)){var g=c,k=r(b.name.replace(a,"")),l;b=b.value;var h=void 0;try{l=b?"true"==b||("false"==b?!1:"null"==b?null:!isNaN(h=Number(b))?h:/^[\[\{]/.test(b)?d.parseJSON(b):b):b}catch(m){l=b}g[k]=l}return c};var r=function(a){return a.replace(/-+(.)?/g,function(a,c){return c?c.toUpperCase():""})};d.fn.parsley.defaults=
{namespace:"parsley-",inputs:"input, textarea, select",excluded:"input[type=hidden], input[type=file], :disabled",priorityEnabled:!0,trigger:!1,animate:!0,animateDuration:300,scrollDuration:500,focus:"first",validationMinlength:3,successClass:"parsley-success",errorClass:"parsley-error",errorMessage:!1,validators:{},showErrors:!0,messages:{},validateIfUnchanged:!1,errors:{classHandler:function(a,b){},container:function(a,b){},errorsWrapper:"<ul></ul>",errorElem:"<li></li>"},listeners:{onFieldValidate:function(a,
b){return!1},onFormValidate:function(a,b,c){},onFieldError:function(a,b,c){},onFieldSuccess:function(a,b,c){}}}}(window.jQuery||window.Zepto);
</script>
	<?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
?>

<script type="text/javascript">
	//Unique namespace
	Duplicator = new Object();

	Duplicator.showProgressBar = function () {
		Duplicator.animateProgressBar('progress-bar');
		$('#ajaxerr-area').hide();
		$('#progress-area').show();
	}
	Duplicator.hideProgressBar = function () {
		$('#progress-area').hide(100);
		$('#ajaxerr-area').fadeIn(400);
	}
	Duplicator.animateProgressBar = function(id) {
		//Create Progress Bar
		var $mainbar   = $("#" + id);
		$mainbar.progressbar({ value: 100 });
		$mainbar.height(25);
		runAnimation($mainbar);

		function runAnimation($pb) {
			$pb.css({ "padding-left": "0%", "padding-right": "90%" });
			$pb.progressbar("option", "value", 100);
			$pb.animate({ paddingLeft: "90%", paddingRight: "0%" }, 3500, "linear", function () { runAnimation($pb); });
		}
	}

	
	Duplicator.toggleMetaBox = function() {
		var $title = jQuery(this);
		var $panel = $title.parent().find('.dup-box-panel');
		var $arrow = $title.parent().find('.dup-box-arrow');
		var value = $panel.is(":visible") ? 0 : 1;
		$panel.toggle();
		(value) ? $arrow.html('-') : $arrow.html('+');
	}	
	
	$(document).ready(function() {
		//ATTACHED EVENTS
		$('#dup-hlp-lnk').change(function() {
			if ($(this).val() != "null") 
				window.open($(this).val())
		});
		
		//Init: Toggle MetaBoxes
		$('div.dup-box div.dup-box-title').each(function() { 
			var $title = $(this);
			var $panel = $title.parent().find('.dup-box-panel');
			var $arrow = $title.find('.dup-box-arrow');
			$title.click(Duplicator.toggleMetaBox); 
			($panel.is(":visible")) ? $arrow.html('-') : $arrow.html('+');
		});
		
	});
</script>

</head>
<body>

<div id="content">
	<!-- =========================================
	HEADER TEMPLATE: Common header on all steps -->
	<table cellspacing="0" class="header-wizard">
		<tr>
			<td style="width:100%;">
				<div style="font-size:22px; padding:5px 0px 0px 0px">
					<!-- !!DO NOT CHANGE/EDIT OR REMOVE PRODUCT NAME!!
					If your interested in Private Label Rights please contact us at the URL below to discuss
					customizations to product labeling: http://lifeinthegrid.com	-->
					&nbsp; Duplicator - Installer
				</div>
			</td>
			<td style="white-space:nowrap; text-align:right">
				<select id="dup-hlp-lnk">
					<option value="null"> - Online Resources -</option>
					<option value="http://lifeinthegrid.com/duplicator-docs">&raquo; Knowledge Base</option>
					<option value="http://lifeinthegrid.com/duplicator-guide">&raquo; User Guide</option>
					<option value="http://lifeinthegrid.com/duplicator-faq">&raquo; Common FAQs</option>
					<option value="http://lifeinthegrid.com/duplicator-hosts">&raquo; Approved Hosts</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				
				<?php if (isset($_GET['help'])) :?>
				<div style="margin:4px 0px 10px 15px; font-size:18px">
					Help Overview
				</div>
				<?php else : ?>
					<?php
					$step1CSS = ($_POST['action_step'] <= 1) ? "active-step" : "complete-step";
					$step2CSS = ($_POST['action_step'] == 2) ? "active-step" : "";

					$step3CSS = "";
					if ($_POST['action_step'] == 3) {
						$step2CSS = "complete-step";
						$step3CSS = "active-step";
					}
					?>
					<div id="dup-wiz">
						<div id="dup-wiz-steps">
							<div class="<?php echo $step1CSS; ?>"><a><span>1</span> Deploy</a></div>
							<div class="<?php echo $step2CSS; ?>"><a><span>2</span> Update </a></div>
							<div class="<?php echo $step3CSS; ?>"><a><span>3</span> Test </a></div>
						</div>
					</div>
				<?php endif; ?>

			</td>
			<td style="white-space:nowrap">

				<i style='font-size:11px; color:#999'>
					version: <?php echo $GLOBALS['FW_DUPLICATOR_VERSION'] ?>&nbsp;&nbsp;<a href="installer.php?help=1" target="_blank">[Help]</a>
				</i> &nbsp;
				
			</td>
		</tr>
	</table>	

	<!-- =========================================
	FORM DATA: Data Steps -->
	<div id="content-inner">
<?php

if (! isset($_GET['help'])) {
switch ($_POST['action_step']) {
	case "1" :
	?> <?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
	//DETECT ARCHIVE FILES
	$zip_file_name  = "No package file found";
	$zip_file_count = 0;
	foreach (glob("*.zip") as $filename) {
		$zip_file_name = $filename;
		$zip_file_count++;
	}
	if ($zip_file_count > 1) {
		$zip_file_name = "Too many zip files in directory";
	}
	
	$req01a = @is_writeable($GLOBALS["CURRENT_ROOT_PATH"]) 	? 'Pass' : 'Fail';
	if (is_dir($GLOBALS["CURRENT_ROOT_PATH"])) {
		if ($dh = @opendir($GLOBALS["CURRENT_ROOT_PATH"])) {
			closedir($dh);
		} else {
			$req01a = 'Fail';
		}
	}
	$req01b = ($zip_file_count == 1) ? 'Pass' : 'Fail';
	$req01  = ($req01a == 'Pass' && $req01b == 'Pass') ? 'Pass' : 'Fail';
	$req02 = (((strtolower(@ini_get('safe_mode'))   == 'on')   
				||  (strtolower(@ini_get('safe_mode')) == 'yes') 
				||  (strtolower(@ini_get('safe_mode')) == 'true') 
				||  (ini_get("safe_mode") == 1 ))) ? 'Fail' : 'Pass';
	$req03  = function_exists('mysqli_connect') ? 'Pass' : 'Fail';
	$php_compare  = version_compare(phpversion(), '5.2.17');
	$req04 = $php_compare >= 0 ? 'Pass' : 'Fail';
	$total_req = ($req01 == 'Pass' && $req02 == 'Pass' && $req03 == 'Pass' && $req04 == 'Pass') ? 'Pass' : 'Fail';
?>

<script type="text/javascript">
	/** **********************************************
	* METHOD:  Performs Ajax post to extract files and create db
	* Timeout (10000000 = 166 minutes) */
	Duplicator.runDeployment = function() {
		
		var $form = $('#dup-step1-input-form');
		$form.parsley('validate');
		if (! $form.parsley('isValid')) {
			return;
		}
	
		var msg =  "Continue installation with the following settings?\n\n";
			msg += "Server: " + $("#dbhost").val() + "\nDatabase: " + $("#dbname").val() + "\n\n";
			msg += "WARNING: Be sure these database parameters are correct!\n";
			msg += "Entering the wrong information WILL overwrite an existing database.\n";
			msg += "Make sure to have backups of all your data before proceeding.\n\n";
			
		var answer = confirm(msg);
		if (answer) {
			$.ajax({
				type: "POST",
				timeout: 10000000,
				dataType: "json",
				url: window.location.href,
				data: $form.serialize(),
				beforeSend: function() {
					Duplicator.showProgressBar();
					$form.hide();
					$('#dup-step1-result-form').show();
				},			
				success: function(data, textStatus, xhr){ 
					if (typeof(data) != 'undefined' && data.pass == 1) {
						$("#ajax-dbhost").val($("#dbhost").val());
						$("#ajax-dbuser").val($("#dbuser").val());
						$("#ajax-dbpass").val($("#dbpass").val());
						$("#ajax-dbname").val($("#dbname").val());
						$("#ajax-dbcharset").val($("#dbcharset").val());
						$("#ajax-dbcollate").val($("#dbcollate").val());
						$("#ajax-logging").val($("#logging").val());
						$("#ajax-json").val(escape(JSON.stringify(data)));
						setTimeout(function() {$('#dup-step1-result-form').submit();}, 200);
						$('#progress-area').fadeOut(700);
					} else {
						Duplicator.hideProgressBar();
					}
				},
				error: function(xhr) { 
					var status = "<b>server code:</b> " + xhr.status + "<br/><b>status:</b> " + xhr.statusText + "<br/><b>response:</b> " +  xhr.responseText;
					$('#ajaxerr-data').html(status);
					Duplicator.hideProgressBar();
				}
			});	
		} 
	};

	/** **********************************************
	* METHOD: Accetps Useage Warning */
	Duplicator.acceptWarning = function() {
		if ($("#accept-warnings").is(':checked')) {
			$("#dup-step1-deploy-btn").removeAttr("disabled");
		} else {
			$("#dup-step1-deploy-btn").attr("disabled", "true");
		}
	};

	/** **********************************************
	* METHOD: Go back on AJAX result view */
	Duplicator.hideErrorResult = function() {
		$('#dup-step1-result-form').hide();			
		$('#dup-step1-input-form').show(200);
	};
	
	/** **********************************************
	* METHOD: Shows results of database connection 
	* Timeout (45000 = 45 secs) */
	Duplicator.dlgTestDB = function () {
		$.ajax({
			type: "POST",
			timeout: 45000,
			url: window.location.href + '?' + 'dbtest=1',
			data: $('#dup-step1-input-form').serialize(),
			success: function(data){ $('#dbconn-test-msg').html(data); },
			error:   function(data){ alert('An error occurred while testing the database connection!  Be sure the install file and package are both in the same directory.'); }
		});
		
		$("#dup-step1-dialog-db").dialog({
			height:400, width:600, modal: true,
			position:['center', 150],
			buttons: {Close: function() {$(this).dialog( "close" );}}
		});
	
		$('#dbconn-test-msg').html("Attempting Connection.  Please wait...");
	};
	
	Duplicator.showDeleteWarning = function () {
		($('#dbaction-empty').prop('checked')) 
			? $('#dup-step1-warning-emptydb').show(300)
			: $('#dup-step1-warning-emptydb').hide(300);
	};
	
	//DOCUMENT LOAD
	$(document).ready(function() {
		$('#dup-step1-dialog-data').appendTo('#dup-step1-result-container');
		$( "input[name='dbaction']").click(Duplicator.showDeleteWarning);
		Duplicator.acceptWarning();
		Duplicator.showDeleteWarning();		
	});
</script>


<!-- =========================================
VIEW: STEP 1- INPUT -->
<form id='dup-step1-input-form' method="post" class="content-form"  parsley-validate>
	<input type="hidden" name="action_ajax" value="1" />
	<input type="hidden" name="action_step" value="1" />
	<input type="hidden" name="package_name"  value="<?php echo $zip_file_name ?>" />
	
	<div class="dup-logfile-link">
		<select name="logging" id="logging">
		    <option value="1" selected="selected">Light Logging</option>
		    <option value="2">Detailed Logging</option>
			<!--option value="3">Debug Logging</option-->
		</select>
	</div>
	<h3 style="margin-bottom:5px">
	    Step 1: Files &amp; Database
	</h3>
	<hr size="1" />
	
	<!-- CHECKS: FAIL -->
	<?php if ( $total_req == 'Fail')  :	?>
	
		<div class="dup-box">
			<div class="dup-box-title">
				<div id="system-circle" class="circle-fail"></div> &nbsp; Requirements: Fail
				<div class="dup-box-arrow"></div>
			</div>
			<div class="dup-box-panel" style="display:none">	
				<div id="dup-step1-result-container"></div>
			</div> 
		</div><br/>
	
    	<i id="dup-step1-sys-req-msg">
			This installation will not be able to proceed until the system requirements pass. Please validate your system requirements by clicking on the button above. 
			In order to get these values to pass please contact your server administrator, hosting provider or visit the online FAQ.
		</i><br/>
    		    
    	<div style="line-height:28px; font-size:14px; padding:0px 0px 0px 30px; font-weight:normal">
    	    <b>Helpful Resources:</b><br/>
    	    &raquo; <a href="http://lifeinthegrid.com/duplicator-faq" target="_blank">Common FAQs</a> <br/>
    	    &raquo; <a href="http://lifeinthegrid.com/duplicator-guide" target="_blank">User Guide</a> <br/>
    	    &raquo; <a href="http://lifeinthegrid.com/duplicator-hosts" target="_blank">Approved Hosts</a> <br/>
    	</div><br/>
	
	<!-- CHECKS: PASS -->
	<?php else : ?>	
	
	
		<div class="dup-box">
			<div class="dup-box-title">
				<div id="system-circle" class="circle-pass"></div> &nbsp; Requirements: Pass
				<div class="dup-box-arrow"></div>
			</div>
			<div class="dup-box-panel" style="display:none">	
				<div id="dup-step1-result-container"></div>
			</div> 
		</div><br/>
	
    	<div class="title-header">
    	    MySQL Database
    	</div>
    	<table class="dup-step1-inputs">
			<tr>
				<td>Action</td>
				<td>
					<div class="dup-step1-modes">
						<input type="radio" name="dbaction" id="dbaction-create" value="create" checked="checked" />
						<label for="dbaction-create">Create New Database</label>
					</div>
					<div class="dup-step1-modes">
						<input type="radio" name="dbaction" id="dbaction-empty" value="empty" />
						<label for="dbaction-empty">Connect and Remove All Data</label>						
					</div>
				</td>
			</tr>			
    	    <tr><td>Host</td><td><input type="text" name="dbhost" id="dbhost" parsley-required="true" value="<?php echo htmlspecialchars($GLOBALS['FW_DBHOST']); ?>" placeholder="localhost" /></td></tr>
			<tr><td>Name</td><td><input type="text" name="dbname" id="dbname"  parsley-required="true" value="<?php echo htmlspecialchars($GLOBALS['FW_DBNAME']); ?>"  placeholder="new or existing database name"  /></td></tr>
			<tr><td>User</td><td><input type="text" name="dbuser" id="dbuser" parsley-required="true" value="<?php echo htmlspecialchars($GLOBALS['FW_DBUSER']); ?>" placeholder="valid database username" /></td></tr>
    	    <tr><td>Password</td><td><input type="text" name="dbpass" id="dbpass" value="<?php echo htmlspecialchars($GLOBALS['FW_DBPASS']); ?>"  placeholder="valid database user password"   /></td></tr>
    	</table>
		
		<div id="dup-step1-dbconn">
			<input id="dup-step1-dbconn-btn" type="button" onclick="Duplicator.dlgTestDB()" style="" value="Test Connection..." />
		</div>


    	<!-- !!DO NOT CHANGE/EDIT OR REMOVE THIS SECTION!!
    	If your interested in Private Label Rights please contact us at the URL below to discuss
    	customizations to product labeling: http://lifeinthegrid.com	-->
    	<a href="javascript:void(0)" onclick="$('#dup-step1-cpanel').toggle(250)"><b>Need Setup Help...</b></a>
    	<div id='dup-step1-cpanel' style="display:none">
    	    <div style="padding:10px 0px 0px 10px;line-height:22px">
    		&raquo; Check out the <a href="http://lifeinthegrid.com/duplicator-tutorials" target="_blank">video tutorials &amp; guides</a> <br/>
    		&raquo; Get help from our <a href="http://lifeinthegrid.com/labs/duplicator/resources/" target="_blank">resources page</a>
    	    </div>
    	</div><br/><br/>
    		    
    	<a href="javascript:void(0)" onclick="$('#dup-step1-adv-opts').toggle(250)"><b>Advanced Options...</b></a>
    	<div id='dup-step1-adv-opts' style="display:none">
    	    <table class="dup-step1-inputs">
    		<tr><td colspan="2"><input type="checkbox" name="zip_manual"  id="zip_manual" value="1" /> <label for="zip_manual">Manual package extraction</label></td></tr>
    		<tr><td colspan="2"><input type="checkbox" name="ssl_admin" id="ssl_admin" <?php echo ($GLOBALS['FW_SSL_ADMIN']) ? "checked='checked'" : ""; ?> /> <label for="ssl_admin">Enforce SSL on Admin</label></td></tr>
			<tr><td colspan="2"><input type="checkbox" name="ssl_login" id="ssl_login" <?php echo ($GLOBALS['FW_SSL_LOGIN']) ? "checked='checked'" : ""; ?> /> <label for="ssl_login">Enforce SSL on Login</label></td></tr>
			<tr><td colspan="2"><input type="checkbox" name="cache_wp" id="cache_wp" <?php echo ($GLOBALS['FW_CACHE_WP']) ? "checked='checked'" : ""; ?> /> <label for="cache_wp">Keep Cache Enabled</label></td></tr>
			<tr><td colspan="2"><input type="checkbox" name="cache_path" id="cache_path" <?php echo ($GLOBALS['FW_CACHE_PATH']) ? "checked='checked'" : ""; ?> /> <label for="cache_path">Keep Cache Home Path</label></td></tr>
    		<tr><td colspan="2"><input type="checkbox" name="dbnbsp" id="dbnbsp" value="1" /> <label for="dbnbsp">Fix non-breaking space characters</label></td></tr>
    		<tr><td style="width:130px">MySQL Charset</td><td><input type="text" name="dbcharset" id="dbcharset" value="<?php echo $_POST['dbcharset'] ?>" /> </td></tr>
    		<tr><td>MySQL Collation </td><td><input type="text" name="dbcollate" id="dbcollate" value="<?php echo $_POST['dbcollate'] ?>" /> </tr>
    	    </table>
    	</div>

		<!-- NOTICES  -->
    	<div id="dup-step1-warning">
    	    <b>WARNINGS &amp; NOTICES</b> 
    	    <p>
				<b>Disclaimer:</b> 
				This plugin require above average technical knowledge. Please use it at your own risk and always back up your database and files beforehand using another backup
				system besides the Duplicator. If you're not sure about how to use this tool then please enlist the guidance of a technical professional.  <u>Always</u> test 
				this installer in a sandbox environment before trying to deploy into a production setting.
			</p>    
    	    <p>
				<b>Database:</b>
				Do not connect to an existing database unless you are 100% sure you want to remove all of it's data. Connecting to a database 
				that already exists will permanently DELETE all data in that database. This tool is designed to populate and fill a database with NEW data from a duplicated
				database using the SQL script in the package name above.
			</p>    
    	    <p>
				<b>Setup:</b>
				Only the archive and installer.php file should be in the install directory, unless you have manually extracted the package and checked the 
				'Manual Package Extraction' checkbox. All other files will be OVERWRITTEN during install.  Make sure you have full backups of all your databases and files 
				before continuing with an installation.</p>    
    	    <p>
				<b>Manual Extraction:</b> 
				Manual extraction requires that all contents in the package are extracted to the same directory as the installer.php file.  
				Manual extraction is only needed when your server does not support the ZipArchive extension.  Please see the online help for more details.
			</p>			    
    	    <p>
				<b>After Install:</b>When you are done with the installation remove the installer.php, installer-data.sql and the installer-log.txt files from your directory. 
				These files contain sensitive information and should not remain on a production system.
			</p><br/>
    	</div>
    		    
    	<div id="dup-step1-warning-check">
    	    <input id="accept-warnings" name="accpet-warnings" type="checkbox" onclick="Duplicator.acceptWarning()" /> <label for="accept-warnings">I have read all warnings &amp; notices</label><br/>
			<div id="dup-step1-warning-emptydb">
				The remove action will delete <u>all</u> tables and data from the database!
			</div>
    	</div><br/><br/><br/>
    		    
    	<div class="dup-footer-buttons">
    	    <input id="dup-step1-deploy-btn" type="button" value=" Run Deployment " onclick="Duplicator.runDeployment()" />
    	</div>				
	<?php endif; ?>	
</form>


<!-- =========================================
VIEW: STEP 1 - AJAX RESULT
Auto Posts to view.step2.php  -->
<form id='dup-step1-result-form' method="post" class="content-form" style="display:none">
	<input type="hidden" name="action_step" value="2" />
	<input type="hidden" name="package_name" value="<?php echo $zip_file_name ?>" />
	<!-- Set via jQuery -->
	<input type="hidden" name="logging" id="ajax-logging"  />	
	<input type="hidden" name="dbhost" id="ajax-dbhost" />
	<input type="hidden" name="dbuser" id="ajax-dbuser" />
	<input type="hidden" name="dbpass" id="ajax-dbpass" />
	<input type="hidden" name="dbname" id="ajax-dbname" />
	<input type="hidden" name="json"   id="ajax-json" />
	<input type="hidden" name="dbcharset" id="ajax-dbcharset" />
	<input type="hidden" name="dbcollate" id="ajax-dbcollate" />
	
    <div class="dup-logfile-link"><a href="installer-log.txt" target="_blank">installer-log.txt</a></div>
	<h3>Step 1: Files &amp; Database</h3>
	<hr size="1" />
	    
	<!--  PROGRESS BAR -->
	<div id="progress-area">
	    <div style="width:500px; margin:auto">
		<h3>Processing Files &amp; Database Please Wait...</h3>
		<div id="progress-bar"></div>
		<i>This may take several minutes</i>
	    </div>
	</div>
	    
	<!--  AJAX SYSTEM ERROR -->
	<div id="ajaxerr-area" style="display:none">
	    <p>Please try again an issue has occurred.</p>
	    <div style="padding: 0px 10px 10px 0px;">
			<div id="ajaxerr-data">An unknown issue has occurred with the file and database setup process.  Please see the installer-log.txt file for more details.</div>
			<div style="text-align:center; margin:10px auto 0px auto">
				<input type="button" onclick='Duplicator.hideErrorResult()' value="&laquo; Try Again" /><br/><br/>
				<i style='font-size:11px'>See online help for more details at <a href='http://lifeinthegrid.com/support' target='_blank'>support.lifeinthegrid.com</a></i>
			</div>
	    </div>
	</div>
</form>


<!-- =========================================
PANEL: SERVER CHECKS  -->
<div id="dup-step1-dialog" title="System Status" style="display:none">
<div id="dup-step1-dialog-data" style="padding: 0px 10px 10px 10px;">
	
	<b>Archive Name:</b> <?php echo $zip_file_name; ?> <br/>
	<b>Pakcage Notes:</b> <?php echo empty($GLOBALS['FW_PACKAGE_NOTES']) ? 'No notes provided for this pakcage.' : $GLOBALS['FW_PACKAGE_NOTES']; ?><br/><br/>
					
	<!-- SYSTEM REQUIREMENTS -->
	<b>REQUIREMENTS</b> &nbsp; <i style='font-size:11px'>click links for details</i>
	<hr size="1"/>
	<table style="width:100%">
	<tr>
		<td style="width:300px"><a href="javascript:void(0)" onclick="$('#dup-SRV01').toggle(400)">Root Directory</a></td>
		<td class="<?php echo ($req01 == 'Pass') ? 'dup-pass' : 'dup-fail' ?>"><?php echo $req01; ?></td>
	</tr>
	<tr>
		<td colspan="2" id="dup-SRV01" class='dup-step1-dialog-data-details'>
		<?php
		echo "<i>Path: {$GLOBALS['CURRENT_ROOT_PATH']} </i><br/>";
		printf("<b>[%s]</b> %s <br/>", $req01a, "Is Writable by PHP");
		printf("<b>[%s]</b> %s <br/>", $req01b, "Contains only one zip file<div style='padding-left:70px'>Result = {$zip_file_name} <br/> <i>Note: Manual extraction still requires the archive.zip file</i> </div> ");
		?>
		</td>
	</tr>
	<tr>
		<td>Safe Mode Off</td>
		<td class="<?php echo ($req02 == 'Pass') ? 'dup-pass' : 'dup-fail' ?>"><?php echo $req02; ?></td>
	</tr>
	<tr>
		<td><a href="http://us2.php.net/manual/en/mysqli.installation.php" target="_blank">MySQLi Support</a></td>
		<td class="<?php echo ($req03 == 'Pass') ? 'dup-pass' : 'dup-fail' ?>"><?php echo $req03; ?></td>
	</tr>
	<tr>
		<td valign="top">
		PHP Version: <?php echo phpversion(); ?><br/>
		<i style="font-size:10px">(PHP 5.2.17+ is required)</i>
		</td>
		<td class="<?php echo ($req04 == 'Pass') ? 'dup-pass' : 'dup-fail' ?>"><?php echo $req04; ?> </td>
	</tr>
	</table>

	<!-- SYSTEM CHECKS -->
	<b>CHECKS</b><hr style='margin-top:-2px' size="1"/>
	<table style="width:100%">
	<tr>
		<td style="width:300px"></td>
		<td></td>
	</tr>
	<tr>
		<?php if (stristr($_SERVER['SERVER_SOFTWARE'], 'apache') !== false): ?>
			<td><b>Web Server:</b> Apache</td>
			<td><div class='dup-pass'>Good</div></td>
		<?php elseif (stristr($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false): ?> 
			<td><b>Web Server:</b> LiteSpeed</td>
			<td><div class='dup-ok'>OK</div></td>
		<?php elseif (stristr($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false): ?> 
			<td><b>Web Server:</b> Nginx</td>
			<td><div class='dup-ok'>OK</div></td>
		<?php elseif (stristr($_SERVER['SERVER_SOFTWARE'], 'lighttpd') !== false): ?> 
			<td><b>Web Server:</b> Lighthttpd</td>
			<td><div class='dup-ok'>OK</div></td>
		<?php elseif (stristr($_SERVER['SERVER_SOFTWARE'], 'iis') !== false): ?> 
			<td><b>Web Server:</b> Microsoft IIS</td>
			<td><div class='dup-ok'>OK</div></td>
		<?php else: ?>
			<td><b>Web Server:</b> Not detected</td>
			<td><div class='dup-fail'>Caution</div></td>
		<?php endif; ?>				
	</tr>
	<tr>
		<?php
		$open_basedir_set = ini_get("open_basedir");
		if (empty($open_basedir_set)):
		?>
			<td><b>Open Base Dir:</b> Off
			<td><div class='dup-pass'>Good</div>
		<?php else: ?>
			<td><b>Open Base Dir:</b> On</td>
			<td><div class='dup-fail'>Caution</div></td>
		<?php endif; ?>
	</tr>
	</table>

	<hr class='dup-dots' />
	<!-- SAPI -->
	<b>PHP SAPI:</b>  <?php echo php_sapi_name(); ?><br/>
	<b>PHP ZIP Archive:</b> <?php echo class_exists('ZipArchive') ? 'Is Installed' : 'Not Installed'; ?> <br/>
	<b>CDN Accessible:</b> <?php echo ( DupUtil::is_url_active("ajax.aspnetcdn.com", 443) && DupUtil::is_url_active("ajax.googleapis.com", 443)) ? 'Yes' : 'No'; ?> <br/>
	Need an <a href='http://lifeinthegrid.com/duplicator-hosts' target='_blank'>approved</a> Duplicator hosting provider?

</div>
</div>


<!-- =========================================
DIALOG: DB CONNECTION CHECK  -->
<div id="dup-step1-dialog-db" title="Connection Test" style="display:none">
    <div id="dup-step1-dialog-db-data" style="padding: 0px 10px 10px 10px;">		
		<div id="dbconn-test-msg" style="min-height:50px"></div>
		<br/>
		<div class="help" style="border-top:1px solid silver">
			<b>Common Connection Issues:</b><br/>
			- Double check case sensitive values 'User', 'Password' &amp; the 'Database Name' <br/>
			- Validate the database and database user exist on this server <br/>
			- Check if the database user has the correct permission levels to this database <br/>
			- The host 'localhost' may not work on all hosting providers <br/>
			- Contact your hosting provider for the exact required parameters <br/>
			- See the 'Database Setup Help' section on step 1 for more details<br/>
			- Visit the online resources 'Common FAQ page' <br/>
		</div>
    </div>
</div> <?php
	break;
	case "2" :
	?> <?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
	$dbh = @mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass'], $_POST['dbname']);

	$all_tables     = DupUtil::get_database_tables($dbh);
	$active_plugins = DupUtil::get_active_plugins($dbh);
	

	$old_path = $GLOBALS['FW_WPROOT'];
	$new_path = DupUtil::set_safe_path($GLOBALS['CURRENT_ROOT_PATH']);
	$new_path = ((strrpos($old_path, '/') + 1) == strlen($old_path)) ? DupUtil::add_slash($new_path) : $new_path; 
?>

<script type="text/javascript">
	/** **********************************************
	* METHOD:  
	* Timeout (10000000 = 166 minutes) */
	Duplicator.runUpdate = function() {
		
		//Validation
		var wp_username = $.trim($("#wp_username").val()).length || 0;
		var wp_password = $.trim($("#wp_password").val()).length || 0;
		
		if ( $.trim($("#url_new").val()) == "" )  {alert("The 'New URL' field is required!"); return false;}
		if ( $.trim($("#siteurl").val()) == "" )  {alert("The 'Site URL' field is required!"); return false;}
		if (wp_username >= 1 && wp_username < 4) {alert("The New Admin Account 'Username' must be four or more characters"); return false;}
		if (wp_username >= 4 && wp_password < 6) {alert("The New Admin Account 'Password' must be six or more characters"); return false;}

		$.ajax({
			type: "POST",
			timeout: 10000000,
			dataType: "json",			
			url: window.location.href,
			data: $('#dup-step2-input-form').serialize(),
			beforeSend: function() {
				Duplicator.showProgressBar();
				$('#dup-step2-input-form').hide();
				$('#dup-step2-result-form').show();
			},			
			success: function(data){ 
				if (typeof(data) != 'undefined' && data.step2.pass == 1) {
					$("#ajax-url_new").val($("#url_new").val());
					$("#ajax-json").val(escape(JSON.stringify(data)));
					setTimeout(function(){$('#dup-step2-result-form').submit();}, 100);
					$('#progress-area').fadeOut(1800);
				} else {
					Duplicator.hideProgressBar();
				}
			},
			error: function(xhr) { 
				var status = "<b>server code:</b> " + xhr.status + "<br/><b>status:</b> " + xhr.statusText + "<br/><b>response:</b> " +  xhr.responseText;
				$('#ajaxerr-data').html(status);
				Duplicator.hideProgressBar();
			}
		});
	};

	/** **********************************************
	* METHOD: Returns the windows active url */
	Duplicator.getNewURL = function(id) {
		var filename= window.location.pathname.split('/').pop() || 'installer.php' ;
		$("#" + id).val(window.location.href.replace(filename, ''));
	};
	
	/** **********************************************
	* METHOD: Allows user to edit the package url  */
	Duplicator.editOldURL = function() {
		var msg = 'This is the URL that was generated when the package was created.\n';
		msg += 'Changing this value may cause issues with the install process.\n\n';
		msg += 'Only modify  this value if you know exactly what the value should be.\n';
		msg += 'See "General Settings" in the WordPress Administrator for more details.\n\n';
		msg += 'Are you sure you want to continue?';
	
		if (confirm(msg)) {
			$("#url_old").removeAttr('readonly');
			$("#url_old").removeClass('readonly');
			$('#edit_url_old').hide('slow');
		}
	};
	
	/** **********************************************
	* METHOD: Allows user to edit the package path  */
	Duplicator.editOldPath = function() {
		var msg = 'This is the SERVER URL that was generated when the package was created.\n';
		msg += 'Changing this value may cause issues with the install process.\n\n';
		msg += 'Only modify  this value if you know exactly what the value should be.\n';
		msg += 'Are you sure you want to continue?';
	
		if (confirm(msg)) {
			$("#path_old").removeAttr('readonly');
			$("#path_old").removeClass('readonly');
			$('#edit_path_old').hide('slow');
		}
	};
	
	/** **********************************************
	* METHOD: Go back on AJAX result view */
	Duplicator.hideErrorResult2 = function() {
		$('#dup-step2-result-form').hide();			
		$('#dup-step2-input-form').show(200);
	};
	
	//DOCUMENT LOAD
	$(document).ready(function() {
		Duplicator.getNewURL('url_new');
		Duplicator.getNewURL('siteurl');
		
		$("#wp_password").passStrength({
				shortPass: 		"top_shortPass",
				badPass:		"top_badPass",
				goodPass:		"top_goodPass",
				strongPass:		"top_strongPass",
				baseStyle:		"top_testresult",
				userid:			"#wp_username",
				messageloc:		1	});
	});
</script>


<!-- =========================================
VIEW: STEP 2- INPUT -->
<form id='dup-step2-input-form' method="post" class="content-form">
	<input type="hidden" name="action_ajax"	 value="2" />
	<input type="hidden" name="action_step"	 value="2" />
	<input type="hidden" name="logging"		 value="<?php echo $_POST['logging'] ?>" />
	<input type="hidden" name="package_name" value="<?php echo $_POST['package_name'] ?>" />
	<input type="hidden" name="json"		 value="<?php echo $_POST['json']; ?>" />
	<input type="hidden" name="dbhost"		 value="<?php echo $_POST['dbhost'] ?>" />
	<input type="hidden" name="dbuser" 		 value="<?php echo $_POST['dbuser'] ?>" />
	<input type="hidden" name="dbpass" 		 value="<?php echo htmlentities($_POST['dbpass']) ?>" />
	<input type="hidden" name="dbname" 		 value="<?php echo $_POST['dbname'] ?>" />
	<input type="hidden" name="dbcharset" 	 value="<?php echo $_POST['dbcharset'] ?>" />
	<input type="hidden" name="dbcollate" 	 value="<?php echo $_POST['dbcollate'] ?>" />
	
	
	<div class="dup-logfile-link"><a href="installer-log.txt" target="_blank">installer-log.txt</a></div>
	<h3>Step 2: Files &amp; Database</h3>
	<hr size="1" /><br />

	<div class="title-header">Old Settings</div>
	<table class="table-inputs-step2">
		<tr valign="top">
			<td style="width:80px">URL</td>
			<td>
				<input type="text" name="url_old" id="url_old" value="<?php echo $GLOBALS['FW_URL_OLD'] ?>" readonly="readonly"  class="readonly" />
				<a href="javascript:Duplicator.editOldURL()" id="edit_url_old" style="font-size:12px">edit</a>		
			</td>
		</tr>
		<tr valign="top">
			<td>Path</td>
			<td>
				<input type="text" name="path_old" id="path_old" value="<?php echo $old_path ?>" readonly="readonly"  class="readonly" />
				<a href="javascript:Duplicator.editOldPath()" id="edit_path_old" style="font-size:12px">edit</a>		
			</td>
		</tr>
	</table>

	<div class="title-header" style="margin-top:8px">New Settings</div>
	<table class="table-inputs-step2">		
		<tr>
			<td style="width:80px">URL</td>
			<td>
				<input type="text" name="url_new" id="url_new" value="<?php echo $GLOBALS['FW_URL_NEW'] ?>" />
				<a href="javascript:Duplicator.getNewURL('url_new')" style="font-size:12px">get</a>
			</td>
		</tr>
		<tr>
			<td>Path</td>
			<td><input type="text" name="path_new" id="path_new" value="<?php echo $new_path ?>" /></td>
		</tr>	
		<tr>
			<td>Title</td>
			<td><input type="text" name="blogname" id="blogname" value="<?php echo $GLOBALS['FW_BLOGNAME'] ?>" /></td>
		</tr>
	</table><br/>
	
	<!-- ==========================
    CREATE NEW USER -->
	<a href="javascript:void(0)" onclick="$('#dup-step2-user-opts').toggle(0)"><b>New Admin Account...</b></a>
	<div id='dup-step2-user-opts' style="display:none;">
	<table class="table-inputs-step2" style="margin-top:7px">
		<tr><td colspan="2"><i style="color:gray;font-size: 11px">This feature is optional.  If the username already exists the account will NOT be created or updated.</i></td></tr>
		<tr>
			<td>Username </td>
			<td><input type="text" name="wp_username" id="wp_username" value="" title="4 characters minimum" placeholder="(4 or more characters)" /></td>
		</tr>	
		<tr>
			<td valign="top">Password</td>
			<td><input type="text" name="wp_password" id="wp_password" value="" title="6 characters minimum"  placeholder="(6 or more characters)" /></td>
		</tr>
	</table>
	</div><br/><br/>
	
	
	<!-- ==========================
    ADVANCED OPTIONS -->
	<a href="javascript:void(0)" onclick="$('#dup-step2-adv-opts').toggle(0)"><b>Advanced Options...</b></a>
	<div id='dup-step2-adv-opts' style="display:none;">
		<table style="width: 100%;">
			<tr>
				<td valign="top" style="width:80px">Site URL</td>
				<td>
					<input type="text" name="siteurl" id="siteurl" value="" />
					<a href="javascript:Duplicator.getNewURL('siteurl')" style="font-size:12px">get</a><br/>
				</td>
			</tr>
		</table><br/>
		

		<table>
			<tr>
				<td style="padding-right:10px">
					Scan Tables
					<div class="dup-step2-allnonelinks">
						<a href="javascript:void(0)" onclick="$('#tables option').prop('selected',true);">[All]</a> 
						<a href="javascript:void(0)" onclick="$('#tables option').prop('selected',false);">[None]</a>
					</div><br style="clear:both" />
					<select id="tables" name="tables[]" multiple="multiple" style="width:315px; height:100px">
						<?php
						foreach( $all_tables as $table ) {
							echo '<option selected="selected" value="' . DupUtil::esc_html_attr( $table ) . '">' . $table . '</option>';
						} 
						?>
					</select>

				</td>
				<td valign="top">
					Activate Plugins
					<div class="dup-step2-allnonelinks">
						<a href="javascript:void(0)" onclick="$('#plugins option').prop('selected',true);">[All]</a> 
						<a href="javascript:void(0)" onclick="$('#plugins option').prop('selected',false);">[None]</a>
					</div><br style="clear:both" />
					<select id="plugins" name="plugins[]" multiple="multiple" style="width:315px; height:100px">
						<?php
						foreach ($active_plugins as $plugin) {
							echo '<option selected="selected" value="' . DupUtil::esc_html_attr( $plugin ) . '">' . dirname($plugin) . '</option>';
						} 
						?>
					</select>
				</td>
			</tr>							
		</table><br/>
		
		<input type="checkbox" name="postguid" id="postguid" value="1" /> <label for="postguid">Keep Post GUID unchanged</label><br/>
		
		<input type="checkbox" name="fullsearch" id="fullsearch" value="1" /> <label for="fullsearch">Enable Full Search <small>(very slow)</small> </label><br/>
		<br/><br/><br/><br/>	
		
	</div>

	<div class="dup-footer-buttons">
		<input id="dup-step2-next" type="button" value=" Run Update " onclick="Duplicator.runUpdate()"  />
	</div>	
</form>


<!-- =========================================
VIEW: STEP 2 - AJAX RESULT  -->
<form id='dup-step2-result-form' method="post" class="content-form" style="display:none">
	<input type="hidden" name="action_step"  value="3" />
	<input type="hidden" name="package_name" value="<?php echo $_POST['package_name'] ?>" />
	<!-- Set via jQuery -->
	<input type="hidden" name="url_new" id="ajax-url_new"  />
	<input type="hidden" name="json"    id="ajax-json" />	
	
	<div class="dup-logfile-link"><a href="installer-log.txt" target="_blank">installer-log.txt</a></div>
	<h3>Step 2: Update Table Data</h3>
	<hr size="1" />
	
	<!--  PROGRESS BAR -->
	<div id="progress-area">
		<div style="width:500px; margin:auto">
			<h3>Processing Data Replacement Please Wait...</h3>
			<div id="progress-bar"></div>
			<i>This may take several minutes</i>
		</div>
	</div>
	
	<!--  AJAX SYSTEM ERROR -->
	<div id="ajaxerr-area" style="display:none">
		<p>Please try again an issue has occurred.</p>
		<div style="padding: 0px 10px 10px 10px;">
			<div id="ajaxerr-data">An unknown issue has occurred with the data replacement setup process.  Please see the installer-log.txt file for more details.</div>
			<div style="text-align:center; margin:10px auto 0px auto">
				<input type="button" onclick='Duplicator.hideErrorResult2()' value="&laquo; Try Again" /><br/><br/>
				<i style='font-size:11px'>See online help for more details at <a href='http://lifeinthegrid.com/support' target='_blank'>support.lifeinthegrid.com</a></i>
			</div>
		</div>
	</div>
</form>

 <?php
	break;
	case "3" :
	?> <?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
?>
<script type="text/javascript">	
	/** **********************************************
	* METHOD: Opens the tips dialog */	
	Duplicator.dlgTips = function() {
		$("#dup-step3-dialog").dialog({
			height:600, width:700, modal: true,
			position:['center', 150],
			buttons: {Close: function() {$(this).dialog( "close" );}}
		});	
	};
	
	/** **********************************************
	* METHOD: Posts to page to remove install files */	
	Duplicator.removeInstallerFiles = function(package_name) {
		var msg = "Delete all installer files now? \n\nThis will remove the page you are now viewing.\nThe page will stay active until you navigate away.";
		if (confirm(msg)) {
			var nurl = '<?php echo rtrim($_POST['url_new'], "/"); ?>/wp-admin/admin.php?page=duplicator-tools&tab=cleanup&action=installer&package=' + package_name;
			window.open(nurl, "_blank");
		}
	};
</script>


<!-- =========================================
VIEW: STEP 3- INPUT -->
<form id='dup-step3-input-form' method="post" class="content-form" style="line-height:20px">
	<input type="hidden" name="url_new" id="url_new" value="<?php echo rtrim($_POST['url_new'], "/"); ?>" />	
	<div class="dup-logfile-link"><a href="installer-log.txt" target="_blank">installer-log.txt</a></div>
	<h3>Step 3: Test Site</h3>
	<hr size="1" /><br />
	
	<div class="title-header">
		<div class="dup-step3-final-title">IMPORTANT FINAL STEPS!</div>
	</div>
		
	<table class="dup-step3-final-step">
		<tr>
			<td>1. <a href="javascript:void(0)" onclick="$('#dup-step3-install-report').toggle(400)">Install Report</a>
			</td>
			<td>
				<i id="dup-step3-install-report-count">
					<b>Errors:</b>
					<span data-bind="with: status.step1">Deploy (<span data-bind="text: query_errs"></span>)</span> &nbsp;
					<span data-bind="with: status.step2">Update (<span data-bind="text: err_all"></span>)</span> &nbsp; &nbsp;
					<span data-bind="with: status.step2" style="color:#888"><b>Warnings:</b> (<span data-bind="text: warn_all"></span>)</span>
				</i>
			</td>
		</tr>	
		<tr>
			<td style="width:170px">
				2. <a href='<?php echo rtrim($_POST['url_new'], "/"); ?>/wp-admin/options-permalink.php' target='_blank'> Save Permalinks</a> 
			</td>
			<td><i>Updates URL rewrite rules in .htaccess (requires login)</i></td>
		</tr>	
		<tr>
			<td>3. <a href='<?php echo $_POST['url_new']; ?>' target='_blank'>Test Site</a></td>
			<td><i>Validate all pages, links images and plugins</i></td>
		</tr>		
		<tr>
			<td>4. <a href="javascript:void(0)" onclick="Duplicator.removeInstallerFiles('<?php echo $_POST['package_name'] ?>')">File Cleanup</a></td>
			<td><i>Removes all installer files (requires login)</i></td>
		</tr>	
	</table><br/>
	
	<div class="dup-step3-go-back">
		<i style='font-size:11px'>To re-install <a href="javascript:history.go(-2)">start over at step 1</a>.</i><br/>
		<i style="font-size:11px;">The .htaccess file was reset.  Resave plugins that write to this file.</i>
	</div>


	<!-- ========================
	INSTALL REPORT -->
	<div id="dup-step3-install-report" style='display:none'>
		<table class='dup-step3-report-results' style="width:100%">
			<tr><th colspan="4">Database Results</th></tr>
			<tr style="font-weight:bold">
				<td style="width:150px"></td>
				<td>Tables</td>
				<td>Rows</td>
				<td>Cells</td>
			</tr>
			<tr data-bind="with: status.step1">
				<td>Created</td>
				<td><span data-bind="text: table_count"></span></td>
				<td><span data-bind="text: table_rows"></span></td>
				<td>n/a</td>
			</tr>	
			<tr data-bind="with: status.step2">
				<td>Scanned</td>
				<td><span data-bind="text: scan_tables"></span></td>        
				<td><span data-bind="text: scan_rows"></span></td>
				<td><span data-bind="text: scan_cells"></span></td>
			</tr>
			<tr data-bind="with: status.step2">
				<td>Updated</td>
				<td><span data-bind="text: updt_tables"></span></td>        
				<td><span data-bind="text: updt_rows"></span></td>
				<td><span data-bind="text: updt_cells"></span></td>
			</tr>
		</table>
		
		<table class='dup-step3-report-errs' style="width:100%; border-top:none">
			<tr><th colspan="4">Errors &amp; Warnings <br/> <i style="font-size:10px; font-weight:normal">(click links below to view details)</i></th></tr>
			<tr>
				<td data-bind="with: status.step1">
					<a href="javascript:void(0);" onclick="$('#dup-step3-errs-create').toggle(400)">Step1: Deploy Errors (<span data-bind="text: query_errs"></span>)</a><br/>
				</td>
				<td data-bind="with: status.step2">
					<a href="javascript:void(0);" onclick="$('#dup-step3-errs-upd').toggle(400)">Step2: Update Errors (<span data-bind="text: err_all"></span>)</a>
				</td>
				<td data-bind="with: status.step2">
					<a href="#dup-step2-errs-warn-anchor" onclick="$('#dup-step3-warnlist').toggle(400)">General Warnings (<span data-bind="text: warn_all"></span>)</a>
				</td>
			</tr>
			<tr><td colspan="4"></td></tr>
		</table>
		
		
		<div id="dup-step3-errs-create" class="dup-step3-err-msg">
		
			<b data-bind="with: status.step1">STEP 1: DEPLOY ERRORS (<span data-bind="text: query_errs"></span>)</b><br/>
			<div class="info">Queries that error during the deploy process are logged to the <a href="installer-log.txt" target="_blank">install-log.txt</a> file.  
			To view the error result look under the section titled 'DATABASE RESULTS'.  If errors are present they will be marked with '**ERROR**'. <br/><br/>  For errors titled
			'Query size limit' you will need to manually post the values or update your mysql server with the max_allowed_packet setting to handle larger payloads.
			If your on a hosted server you will need to contact the server admin, for more details see: https://dev.mysql.com/doc/refman/5.5/en/packet-too-large.html. <br/><br/>
			</div>
			
		</div>
		

		<div id="dup-step3-errs-upd" class="dup-step3-err-msg">
		
			<!-- MYSQL QUERY ERRORS -->
			<b data-bind="with: status.step2">STEP2: UPDATE ERRORS (<span data-bind="text: errsql_sum"></span>) </b><br/>
			<div class="info">Errors that show here are the result of queries that could not be performed.</div>
			<div class="content">
				<div data-bind="foreach: status.step2.errsql"><div data-bind="text: $data"></div></div>
				<div data-bind="visible: status.step2.errsql.length == 0">No MySQL query errors found</div>
			</div>
			
			<!-- TABLE KEY ERRORS -->
			<b data-bind="with: status.step2">TABLE KEY ERRORS (<span data-bind="text: errkey_sum"></span>)</b><br/>
			<div class="info">
				A primary key is required on a table to efficiently run the update engine. Below is a list of tables and the rows that will need to 
				be manually updated.  Use the query below to find the data.<br/>
				<i>SELECT @row := @row + 1 as row, t.* FROM some_table t, (SELECT @row := 0) r</i>
			</div>
			<div class="content">
				<div data-bind="foreach: status.step2.errkey"><div data-bind="text: $data"></div></div>
				<div data-bind="visible: status.step2.errkey.length == 0">No missing primary key errors</div>
			</div>
			
			<!-- SERIALIZE ERRORS -->
			<b data-bind="with: status.step2">SERIALIZATION ERRORS  (<span data-bind="text: errser_sum"></span>)</b><br/>
			<div class="info">
				Use the SQL below to display data that may have not been updated correctly during the serialization process.
			</div>
			<div class="content">
				<div data-bind="foreach: status.step2.errser"><div data-bind="text: $data"></div></div>
				<div data-bind="visible: status.step2.errser.length == 0">No serialization errors found</div>
			</div>			
			
		</div>
		
		
		<!-- WARNINGS-->
		<div id="dup-step3-warnlist" class="dup-step3-err-msg">
			<a href="#" id="dup-step2-errs-warn-anchor"></a>
			<b>GENERAL WARNINGS</b><br/>
			<div class="info">
				The following is a list of warnings that may need to be fixed in order to finalize your setup.  For more details about
				warnings see the <a href="http://codex.wordpress.org/" target="_blank">wordpress codex.</a>.
			</div>
			<div class="content">
				<div data-bind="foreach: status.step2.warnlist">
					 <div data-bind="text: $data"></div>
				</div>
				<div data-bind="visible: status.step2.warnlist.length == 0">
					No warnings found
				</div>
			</div>
		</div><br/>
		
		
	</div><br/><br/>



		
	<div class='dup-step3-connect'>
		Please consider <a href='http://lifeinthegrid.com/partner/' target='_blank'>Partnering or a Donation</a>! <br/>
		<a href="javascript:void(0)" onclick="Duplicator.dlgTips()">Troubleshoot</a> | 
		<a href='http://support.lifeinthegrid.com/knowledgebase.php' target='_blank'>FAQs</a> | 
		<a href='http://lifeinthegrid.com/duplicator' target='_blank'>Support</a>
	</div><br/>
</form>


 <!-- =========================================
DIALOG: TROUBLSHOOTING DIALOG -->
<div id="dup-step3-dialog" title="Troubleshooting Tips" style="display:none">
	<div style="padding: 0px 10px 10px 10px;">		
		<b>Common Quick Fix Issues:</b>
		<ul>
			<li>Use an <a href='http://lifeinthegrid.com/duplicator-hosts' target='_blank'>approved hosting provider</a></li>
			<li>Validate directory and file permissions (see below)</li>
			<li>Validate web server configuration file (see below)</li>
			<li>Clear your browsers cache</li>
			<li>Deactivate and reactivate all plugins</li>
			<li>Resave a plugins settings if it reports errors</li>
			<li>Make sure your root directory is empty</li>
		</ul>

		<b>Permissions:</b><br/> 
		Not all operating systems are alike.  Therefore, when you move a package (zip file) from one location to another the file and directory permissions may not always stick.  If this is the case then check your WordPress directories and make sure it's permissions are set to 755. For files make sure the permissions are set to 644 (this does not apply to windows servers).   Also pay attention to the owner/group attributes.  For a full overview of the correct file changes see the <a href='http://codex.wordpress.org/Hardening_WordPress#File_permissions' target='_blank'>WordPress permissions codex</a>
		<br/><br/>

		<b>Web server configuration files:</b><br/>
		For Apache web server the root .htaccess file was copied to .htaccess.orig. A new stripped down .htaccess file was created to help simplify access issues.  For IIS web server the web.config file was copied to web.config.orig, however no new web.config file was created.  If you have not altered this file manually then resaving your permalinks and resaving your plugins should resolve most all changes that were made to the root web configuration file.   If your still experiencing issues then open the .orig file and do a compare to see what changes need to be made. <br/><br/><b>Plugin Notes:</b><br/> It's impossible to know how all 3rd party plugins function.  The Duplicator attempts to fix the new install URL for settings stored in the WordPress options table.   Please validate that all plugins retained there settings after installing.   If you experience issues try to bulk deactivate all plugins then bulk reactivate them on your new duplicated site. If you run into issues were a plugin does not retain its data then try to resave the plugins settings.
		<br/><br/>
		 
		 <b>Cache Systems:</b><br/>
		 Any type of cache system such as Super Cache, W3 Cache, etc. should be emptied before you create a package.  Another alternative is to include the cache directory in the directory exclusion path list found in the options dialog. Including a directory such as \pathtowordpress\wp-content\w3tc\ (the w3 Total Cache directory) will exclude this directory from being packaged. In is highly recommended to always perform a cache empty when you first fire up your new site even if you excluded your cache directory.
		 <br/><br/>
		 
		 <b>Trying Again:</b><br/>
		 If you need to retry and reinstall this package you can easily run the process again by deleting all files except the installer.php and package file and then browse to the installer.php again.
		 <br/><br/>
		 
		 <b>Additional Notes:</b><br/>
		 If you have made changes to your PHP files directly this might have an impact on your duplicated site.  Be sure all changes made will correspond to the sites new location. 
		 Only the package (zip file) and the installer.php file should be in the directory where you are installing the site.  Please read through our knowledge base before submitting any issues. 
		 If you have a large log file that needs evaluated please email the file, or attach it to a help ticket.
		 <br/><br/>
		 
	</div>
</div>

<script type="text/javascript">
	MyViewModel = function() { 
		this.status = <?php echo urldecode($_POST['json']); ?>;
		var errorCount =  this.status.step2.err_all || 0;
		(errorCount >= 1 )
			? $('#dup-step3-install-report-count').css('color', '#BE2323')
			: $('#dup-step3-install-report-count').css('color', '#197713')
	};
	ko.applyBindings(new MyViewModel());
</script>
 
  <?php
	break;
}
} else {
	?> <?php
	// Exit if accessed directly
	if (! defined('DUPLICATOR_INIT')) {
		$_baseURL =  strlen($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$_baseURL =  "http://" . $_baseURL;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $_baseURL");
		exit; 
	}
?>
<!-- =========================================
HELP FORM -->
<div id="dup-main-help">
<div style="text-align:center">For in-depth help please see the <a href="http://lifeinthegrid.com/duplicator-docs" target="_blank">online resources</a></div>

<h3>Step 1 - Deploy</h3>
<div id="dup-help-step1" class="dup-help-page">
	<!-- MYSQL SERVER -->
	<fieldset>
		<legend><b>MySQL Server</b></legend>

		<b>Action:</b><br/>
		'Create New' will attempt to create a new database if it does not exist.  This option will not work on many hosting providers.  If the database does not exist then you will need to login to your control panel and create the database.  If 'Connect and Remove All Data' is checked this will DELETE all tables in the database you are connecting to as the Duplicator requires a blank database.  Please make sure you have backups of all your data before using an portion of the installer, as this option WILL remove all data.  Please contact your server administrator for more details.
		<br/><br/>

		<b>Host:</b><br/>
		The name of the host server that the database resides on.  Many times this will be localhost, however each hosting provider will have it's own naming convention please check with your server administrator.
		<br/><br/>

		<b>User:</b><br/>
		The name of a MySQL database server user. This is special account that has privileges to access a database and can read from or write to that database.  <i style='font-size:11px'>This is <b>not</b> the same thing as your WordPress administrator account</i> 
		<br/><br/>

		<b>Password:</b><br/>
		The password of the MySQL database server user.
		<br/><br/>

		<b>Name:</b><br/>
		The name of the database to which this installation will connect and install the new tables onto.
		<br/><br/>


	</fieldset>				

	<!-- ADVANCED OPTS -->
	<fieldset>
		<legend><b>Advanced Options</b></legend>
		<b>Manual Package Extraction:</b><br/>
		This allows you to manually extract the zip archive on your own. This can be useful if your system does not have the ZipArchive support enabled.
		<br/><br/>		

		<b>Enforce SSL on Admin:</b><br/>
		Turn off SSL support for WordPress. This sets FORCE_SSL_ADMIN in your wp-config file to false if true, otherwise it will create the setting if not set.
		<br/><br/>	

		<b>Enforce SSL on Login:</b><br/>
		Turn off SSL support for WordPress Logins. This sets FORCE_SSL_LOGIN in your wp-config file to false if true, otherwise it will create the setting if not set.
		<br/><br/>			

		<b>Keep Cache Enabled:</b><br/>
		Turn off Cache support for WordPress. This sets WP_CACHE in your wp-config file to false if true, otherwise it will create the setting if not set.
		<br/><br/>	

		<b>Keep Cache Home Path:</b><br/>
		This sets WPCACHEHOME in your wp-config file to nothing if true, otherwise nothing is changed.
		<br/><br/>	

		<b>Fix non-breaking space characters:</b><br/>
		The process will remove utf8 characters represented as 'xC2' 'xA0' and replace with a uniform space.  Use this option if you find strange question marks in you posts
		<br/><br/>	

		<b>MySQL Charset &amp; MySQL Collation:</b><br/>
		When the database is populated from the SQL script it will use this value as part of its connection.  Only change this value if you know what your databases character set should be.
		<br/>				
	</fieldset>			
</div>

<h3>Step 2 - Update</h3>
<div id="dup-help-step1" class="dup-help-page">

	<!-- SETTINGS-->
	<fieldset>
		<legend><b>Settings</b></legend>
		<b>Old Settings:</b><br/>
		The URL and Path settings are the original values that the package was created with.  These values should not be changed.
		<br/><br/>

		<b>New Settings:</b><br/>
		These are the new values (URL, Path and Title) you can update for the new location at which your site will be installed at.
		<br/>		
	</fieldset>

	<!-- NEW ADMIN ACCOUNT-->
	<fieldset>
		<legend><b>New Admin Account</b></legend>
		<b>Username:</b><br/>
		The new username to create.  This will create a new WordPress administrator account.  Please note that usernames are not changeable from the within the UI.
		<br/><br/>

		<b>Password:</b><br/>
		The new password for the user. 
		<br/>		
	</fieldset>

	<!-- ADVANCED OPTS -->
	<fieldset>
		<legend><b>Advanced Options</b></legend>
		<b>Site URL:</b><br/>
		For details see WordPress <a href="http://codex.wordpress.org/Changing_The_Site_URL" target="_blank">Site URL</a> &amp; <a href="http://codex.wordpress.org/Giving_WordPress_Its_Own_Directory" target="_blank">Alternate Directory</a>.  If you're not sure about this value then leave it the same as the new settings URL.
		<br/><br/>

		<b>Scan Tables:</b><br/>
		Select the tables to be updated. This process will update all of the 'Old Settings' with the 'New Settings'. Hold down the 'ctrl key' to select/deselect multiple.
		<br/><br/>

		<b>Activate Plugins:</b><br/>
		These plug-ins are the plug-ins that were activated when the package was created and represent the plug-ins that will be activated after the install.
		<br/><br/>

		<b>Post GUID:</b><br/>
		If your moving a site keep this value checked. For more details see the <a href="http://codex.wordpress.org/Changing_The_Site_URL#Important_GUID_Note" target="_blank">notes on GUIDS</a>.	Changing values in the posts table GUID column can change RSS readers to evaluate that the posts are new and may show them in feeds again.		
		<br/><br/>	
		
		<b>Full Search:</b><br/>
		Full search forces a scan of every single cell in the database. If it is not checked then only text based columns are searched which makes the update process much faster.
		<br/>	
	</fieldset>

</div>

<h3>Step 3 - Test</h3>
<fieldset>
	<legend><b>Final Steps</b></legend>

	<b>Resave Permalinks</b><br/>
	Re-saving your perma-links will reconfigure your .htaccess file to match the correct path on your server.  This step requires logging back into the WordPress administrator.
	<br/><br/>

	<b>Delete Installer Files</b><br/>
	When you're completed with the installation please delete all installer files.  Leaving these files on your server can impose a security risk!
	<br/><br/>

	<b>Test Entire Site</b><br/>
	After the install is complete run through your entire site and test all pages and posts.
	<br/><br/>

	<b>View Install Report</b><br/>
	The install report is designed to give you a synopsis of the possible errors and warnings that may exist after the installation is completed.
	<br/>
</fieldset>
<br/><br/>
</div> <?php
}
	
?>
	</div>
</div><br/>





</body>
</html>