<?php

/**
 *  Copyright (C) WPDevMate - All Rights Reserved
 *  http://www.wpdevmate.com
 *  https://github.com/lucidpixel/WPDevMate
 *
 */


/* 
 * This file contains all the functions used in WPDevMate
 *
 * Chris Moore 2014, chrisjamesmoore@gmail.com
 *  
 */

include( 'includes/variables.php' );
include( 'includes/lib/phpseclib/Net/SFTP.php' );

$wpdm_config_settings = parse_ini_file( 'config.ini', 'my-function' ) 
    or die( 'Unable to open the config file (includes/config.ini). <br/>' );


/**************************************************************************************
* error reporting
**************************************************************************************/

error_reporting( E_ALL ^ E_DEPRECATED );
ini_set( 'log_errors', 1 );
ini_set( 'error_log', '../error.log' );
ini_set( 'max_execution_time', 0 );
ini_set( 'date.timezone', WPDM_TIME_ZONE );

/**
 * Check license
 * 
 */
function wpdm_check_license() {
    //if( !( ini_get(allow_url_fopen) ) ) exit('Configuration Error: allow_url_fopen must be turned on for this script to work');
    //$lines = file('http://www.lucidpixel.co.uk/products/wpdevmate.txt'); 
    $lines = file( str_replace( '\\', '/', getcwd() ) . '/wpdevmate.txt' ); 
    foreach ( $lines as $line_num => $line ) { 
        $license = htmlspecialchars( $line ); 
        if ( $license == "kill" || "expired" ) { 
            exit( "<font color=black>Your Script License Has Been Terminated<br/>Please Contact <a href=\"mailto:info@wpdevmate.com\"><font color=black>WPDevMate</a> Immediately</font>" ); 
        } 
    } 
}


/**
 * Check if cURL is enabled
 * 
 */
function wpdm_check_curl_enabled() {
    if ( !function_exists( 'curl_init' ) || !in_array( 'curl', get_loaded_extensions() ) ) {
        return false;
    } else {
        return true;
    }
}

/**
 * Check if connected to the internet
 *
 * @param $host_name (string)
 *
 */
function wpdm_check_internet_connection( $host_name ) {
    try {
        //Initiates a socket connection to www.itechroom.com at port 80
        $connection = @fsockopen( $host_name, 80, $errno, $errstr, 30 ); 
        if ( $connection ) { 
            //$status = "YES"; 
            $status = 1; 
            fclose( $connection );
        } else {
            $status = 0;
        }
        return $status; 
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**************************************************************************************
* file related
**************************************************************************************/

/**
 * Return a list of files in a directory
 *
 * @param $directory (string)
 * @param $file_type (string) - zip
 * 
 */
function wpdm_get_files_in_dir( $directory, $file_type ) {
    try {
        $dir     = new DirectoryIterator( $directory );
        $myarray = array();
        //$i = 0;
        foreach ( $dir as $fileinfo ) {
            if ( $fileinfo == '.' || $fileinfo == '..' ) {
            } else {
                //http://uk1.php.net/DirectoryIterator.isFile
                if ( $dir->isDot() ) {
                    continue;
                } //only list zip files
                if ( strripos( $fileinfo, $file_type ) == true ) {
                    $myarray[] = $fileinfo->getFilename();
                }
            }
        }
        return $myarray;
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Copy a file and place it in destination folder
 *
 * @param $src (string) 
 * @param $dest (string) 
 * 
 */
function wpdm_stream_copy( $src, $dest ) {
    try {
        $fsrc  = fopen( $src, 'r' );
        $fdest = fopen( $dest, 'w+' );
        $len   = stream_copy_to_stream($fsrc, $fdest); 
        fclose( $fsrc );
        fclose( $fdest );
        return $len;
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Make a directory
 *
 * @param $path (string) 
 * 
 */
function wpdm_make_dir( $path ) {
    try {
        $ret = "";
        if ( !file_exists( $path ) ) {
            $ret = mkdir( $path, 0777, true ); // use @mkdir if you want to suppress warnings/errors
        }
        return $ret === true || is_dir( $path );
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Create a file
 *
 * @param $dest (string) 
 * @param $str (string) 
 * 
 */
function wpdm_create_file( $dest, $str ) {
    try {
        $fp = fopen( $dest, "w" );
        fwrite( $fp, $str );
        fclose( $fp );
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Write a string of text to a file
 *
 * @param $file (string) 
 * @param $text_to_add (string) 
 * 
 */
function wpdm_write_to_file( $file, $text_to_add ) {
    try {
        $fp = fopen( $file, "a" );
        fwrite( $fp, $text_to_add );
        fclose( $fp );
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Get the string between two blocks of text
 * Used in deletting lines from vhosts
 *
 * @param $content (string) 
 * @param $start (string) 
 * @param $end (string) 
 * 
 */
function wpdm_get_string_between( $content, $start, $end ) {
    try {
        $r = explode( $start, $content );
        if ( isset( $r[1]) ) {
            $r = explode( $end, $r[1] );
            return $r[0];
        }
        return '';
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Find and replace a string in a file
 *
 * @param $file (string) 
 * @param $old_str (string)  (string) 
 * @param $new_str (string) 
 * 
 */
function wpdm_find_and_replace_in_file( $file, $old_str, $new_str ) {
    try {
        $str_to_write = str_replace( $old_str, $new_str, file_get_contents( $file ) );
        file_put_contents( $file, $str_to_write );
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Check if a string exists in a file
 *
 * @param $file (string) 
 * @param $str (string) 
 * 
 */
function wpdm_check_string_exists( $file, $str ) {
    try {
        $the_file = file_get_contents( $file );
        if ( !strpos( $the_file, $str ) ) {
            //echo "String not found!";
            return false;
        } else {
            //echo "String found!";
            return true;
        }
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Check if string (exact match) exists in a file
 *
 * @param $file (string) 
 * @param $str (string) 
 * 
 */
function wpdm_check_string_exists_exact_match( $file, $str ) {
    try {

        if ( file_exists( $file ) ) {
            $contents = file_get_contents( $file );
        } else {
            exit( $file . ' does not exist.');
        }

       if ( preg_match( "/\b".preg_quote( $str )."\b/i", $contents ) ) {
            return true;
        } else {
            return false;
        }
    } catch( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * if string is in text file - delete that line
 * used to cleanup database file and remove drop table statements
 *
 * @param $file_path (string) 
 * @param $str (string) 
 * 
 */
function wpdm_delete_line_from_file( $file_path, $str ) {
    try {
        $file_arr = file( $file_path );
        
        foreach ( $file_arr as $key => $value ) {
            if ( strpos( $value, $str ) !== false ) {
                unset( $file_arr[$key] );
            }
        }
        $success = false;
        if ( file_put_contents( $file_path, implode( '', $file_arr ), LOCK_EX ) ) {
            $success = true;
        }
    } catch ( Exception $e ) 
    {
        echo 'Caught exception in function: ' . '<strong>' .  __FUNCTION__  . '</strong>:<br/>' , $e->getMessage(), '<br/>';
        error_log( 'Error in function: ' . __FUNCTION__  . ' - ' . $e->getMessage() );
    }
}

/**
 * Zip a directory - exlude parent folder
 *
 * @param $directory (string) 
 * @param $file_type (string) 
 * 
 */
class ExtendedZip extends ZipArchive {
    // Member function to add a whole file system subtree to the archive
    public function addTree( $dirname, $localname = '', $ignoreFiles ) {
        if ( $localname )
            $this->addEmptyDir( $localname );
        $this->_addTree( $dirname, $localname, $ignoreFiles );
    }
    
    // Internal function, to recurse
    protected function _addTree( $dirname, $localname, $ignoreFiles ) {
        try {
            $dir = opendir( $dirname );
            
            while ( $filename = readdir( $dir ) ) {
                // Discard . and ..
                if ($filename == '.' || $filename == '..')
                    continue;
                // ignore files & folders
                elseif ( in_array( $filename, $ignoreFiles ) ) {
                    //* The current file is to be excluded.
                    continue;
                }
                // Proceed according to type
                $path      = $dirname . '/' . $filename;
                $localpath = $localname ? ( $localname . '/' . $filename ) : $filename;
                
                if ( is_dir( $path ) ) {
                    // Directory: add & recurse
                    $this->addEmptyDir( $localpath );
                    $this->_addTree( $path, $localpath, $ignoreFiles );
                } else if ( is_file( $path ) ) {
                    // File: just add
                    $this->addFile( $path, $localpath );
                }
            }
            closedir( $dir );
        } catch ( Exception $e ) {
            echo 'Caught exception: ' . $e->getMessage();
        }
    }
    // Helper function
    public static function zipTree( $dirname, $zipFilename, $ignoreFiles, $flags = 0, $localname = '' ) {
        try {
            $zip = new self();
            $zip->open( $zipFilename, $flags );
            $zip->addTree( $dirname, $localname, $ignoreFiles );
            $zip->close();
        } catch ( Exception $e ) {
            echo 'Caught exception: ' . $e->getMessage();
        }
    }
}

/**
 * Removes website folder
 * Just tell it what directory you want deleted, in relation to the page that this function is executed. 
 * Then set $empty = true if you want the folder just emptied, but not deleted. 
 * If you set $empty = false, or just simply leave it out, the given directory will be deleted, as well. 
 *
 * @param $directory (string) 
 * @param $empty (bool)
 * 
 */
function wpdm_delete_all( $directory, $empty = false ) {
    try {
        if (substr( $directory, -1 ) == "/" ) {
            $directory = substr( $directory, 0, -1 );
        }
        if ( !file_exists( $directory ) || !is_dir( $directory ) ) {
            return false;
        } elseif ( !is_readable( $directory ) ) {
            return false;
        } else {
            $directory_handle = opendir( $directory );
            while ( $contents = readdir( $directory_handle ) ) {
                if ( $contents != '.' && $contents != '..' ) {
                    $path = $directory . "/" . $contents;
                    
                    if ( is_dir( $path ) ) {
                        wpdm_delete_all( $path );
                    } else {
                        unlink( $path );
                    }
                }
            }
            closedir( $directory_handle );
            
            if ( $empty == false ) {
                if ( !rmdir( $directory ) ) {
                    return false; 
                }
            }
            return true;
        }
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * remove a directory
 *
 * @param $dir (string) 
 * 
 */
function wpdm_remove_dir( $dir ) {
    try {
        if ( is_dir( $dir ) ) {
            $objects = scandir( $dir );
            foreach ( $objects as $object ) {
                if ( $object != "." && $object != ".." ) {
                    if ( filetype( $dir . "/" . $object ) == "dir")
                        rmdir( $dir . "/" . $object );
                    else
                        unlink( $dir . "/" . $object );
                }
            }
            reset($objects);
            rmdir($dir);
        }
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * return the file extension of a file
 *
 * @param $str (string) 
 * 
 */
function wpdm_get_file_extension( $str ) {
    try {
        $i = strrpos( $str, "." );
        if ( !$i ) {
            return "";
        }
        $l   = strlen( $str ) - $i;
        $ext = substr( $str, $i + 1, $l );
        return $ext;
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}


/**
 * Check sFTP connection
 *
 * @param $server (string) 
 * @param $username (string) 
 * @param $password (string) 
 * 
 */
function wpdm_check_sftp_connection( $server, $username, $password ) {
    try {
        $sftp = new Net_SFTP( $server );

        if (!$sftp->login( $username, $password ) ) {
            //exit('Login Failed');
            return false;
        } else {
            //echo 'Login OK';
            return true;
        }
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Upload files via sFTP
 *
 * @param $server (string) 
 * @param $username (string) 
 * @param $password (string) 
 * @param $remote_path (string) 
 * @param $local_path (array)
 * 
 */
function wpdm_upload_files_sftp( $server, $username, $password, $remote_path, $local_path ) {
    try {
        $sftp = new Net_SFTP( $server );

        if (!$sftp->login( $username, $password ) ) {
            return false;
            die();
        } else {
            return true;
        }
/*        foreach ( $files as $filename ) {
            $sftp->put( $remote_path, $filename, NET_SFTP_LOCAL_FILE );
        }*/
        $sftp->put( $remote_path, $filename, NET_SFTP_LOCAL_FILE );

        } catch ( Exception $e ) {
            echo 'Caught exception: ' . $e->getMessage();
        }
}

/**
 * Check FTP connection
 *
 * @param $server (string) 
 * @param $port (string) 
 * @param $user (string) 
 * @param $pass (string) 
 * 
 */
function wpdm_check_ftp_connection( $server, $port, $user, $pass ) {
    try {   
        error_reporting(0);
        $connect = ftp_connect( $server, $port, 20 ) 
            or die( 'Unable to connect to host.<br/> 
                    Please ensure that the host is available and that you 
                    have entered the correct information.<br/>' );

        ftp_login( $connect, $user, $pass ) 
            or die( 'Authorization Failed.<br/> 
                    Please ensure that the details you have
                    entered are correct.<br/>' );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
    ftp_close( $connect );
}

/**
 * Upload files via FTP
 *
 * @param $server (string) 
 * @param $port (string) 
 * @param $user (string) 
 * @param $pass (string) 
 * @param $destination (string) 
 * @param $files (array)
 * 
 */
function wpdm_upload_files_ftp( $server, $port, $user, $pass, $destination, Array $files ) {
    try {
        $connect = ftp_connect( $server, $port, 20 ) or die( "Unable to connect to host<br/>" );
        ftp_login( $connect, $user, $pass ) or die( "Authorization Failed<br/>" );

        // turn passive mode on
        ftp_pasv( $connect, true );

        foreach ( $files as $filename ) {
            $file         = basename( $filename );
            $fp           = fopen( $filename, "r" );

            // get the file extension

            $getExtension = wpdm_get_file_extension( $filename );

            switch ( $getExtension ) {
                case 'php':
                    $ftp_mode = FTP_ASCII;
                    break;
                
                case 'zip':
                    $ftp_mode = FTP_BINARY;
                    break;
            }
            if ( ftp_fput( $connect, $destination . '/' . $file, $fp, $ftp_mode ) ) { // $file = has to be file, NOT path
            } else {
                echo "<p>Error uploading <strong>$filename</strong>.</p> <br/><br/>";
            }
        }
        ftp_close( $connect );
        fclose( $fp );
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Simple function to download a file using curl
 *
 * @param $remote_url (string) 
 * @param $local_file (string) 
 * 
 */
function wpdm_http_get_file( $remote_url, $local_file ) {
    try {
        $fh = fopen( $local_file, 'w' );
        $ch = curl_init( $remote_url );
        curl_setopt( $ch, CURLOPT_FILE, $fh );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_exec( $ch );
        //print_r( curl_getinfo( $ch ) );
        curl_close( $ch );
        fclose( $fh );
        return true;
        } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Extract a zip archive to a given folder
 *
 * @param $zip_file (string) 
 * @param $extract_to_folder (string) 
 * @param $create_folder (bool)
 * 
 */
function wpdm_extract_zip_latest( $zip_file, $extract_to_folder, $create_folder = false ) {
    try {
        $zip = new ZipArchive();

        // open archive
        if ( $zip->open( $zip_file ) !== true ) {
            die( 'Could not open archive' );
        }
        if ( $create_folder ) {
            // non-wordpress archives are not in a folder, so create it
            $zip->extractTo( str_replace( '\\', '/', getcwd() ) . '/' .  WPDM_WEBSITES_DIR . '/wordpress' ); 
        } else {
            // extract to websites dir
            $zip->extractTo( str_replace( '\\', '/', getcwd() ) . '/' .  WPDM_WEBSITES_DIR ); 
        }
        // close archive
        $zip->close();
        error_reporting( 0 );
        rename( str_replace( '\\', '/', getcwd() ) . '/' .  WPDM_WEBSITES_DIR . '/wordpress', $extract_to_folder );

        return true;
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Extract a zip archive to a given folder
 *
 * @param $zip_file (string) 
 * @param $extract_to_folder (string) 
 * @param $create_folder (bool)
 * 
 */
function wpdm_extract_zip_nightly( $zip_file, $extract_to_folder, $create_folder = false ) {
    try {
        $zip = new ZipArchive();
        // open archive
        if ( $zip->open( $zip_file ) !== true ) {
            die( 'Could not open archive' );
        }
        if ( $create_folder ) {
            // non-wordpress archives are not in a folder, so create it
            $zip->extractTo( str_replace( '\\', '/', getcwd() ) . '/' .  WPDM_WEBSITES_DIR . '/wordpress' ); 
        } else {

            // extract to websites dir
            $zip->extractTo( str_replace( '\\', '/', getcwd() ) . '/' .  WPDM_WEBSITES_DIR ); // J:/WPDevMate/webroot/wpdevmate/websites
        }
        // close archive
        $zip->close();
        error_reporting( 0 );
        rename( str_replace( '\\', '/', getcwd() ) . '/' .  WPDM_WEBSITES_DIR . '/wordpress', $extract_to_folder );

        return true;
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Extract a zip archive to a given folder 
 * (used for custom builds)
 *
 * @param $zip_file (string) 
 * @param $extract_to_folder (string) 
 * 
 */
function wpdm_extract_zip_custombuilds( $zip_file, $extract_to_folder ) {
    try {
        $zip = new ZipArchive();
        // open archive
        if ( $zip->open( $zip_file ) !== true ) {
            die( 'Could not open archive' );
        } 
        $zip->extractTo( str_replace( '\\', '/', getcwd() ) . '/'. WPDM_WEBSITES_DIR . '/wordpress_tmp' );
        $zip->close();

        error_reporting(0);
        rename(str_replace( '\\', '/', getcwd() ) . '/'. WPDM_WEBSITES_DIR . '/wordpress_tmp', $extract_to_folder );

        return true;
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Extract a single file from a zip archive to a given folder 
 * [dir, thezipfile,file to extract]
 *
 * @param $directory (string) 
 * @param $zip_file (string) 
 * @param $file (string) 
 * 
 */
function wpdm_extract_a_file( $directory, $zip_file, $file ) {
    try {
        $zip = new ZipArchive;
        $res = $zip->open( $directory . '/'. $zip_file );
        if ( $res === true ) {
        $zip->extractTo( $directory, $file );
        $zip->close();
        } else {
        }
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**************************************************************************************
* vHosts
**************************************************************************************/

/**
 * Add first section to httpd-vhosts.conf
 *
 * @param $doc_root (string) 
 * 
 */
function wpdm_add_vhost_entry_1( $doc_root ) {
    $str_dr = "<Directory $doc_root>\n";
    $str_dr .= "   AllowOverride All\n";
    $str_dr .= "   Order Allow,Deny\n";
    $str_dr .= "   Allow from all\n";
    $str_dr .= "</Directory>\n";

    wpdm_write_to_file( WPDM_APACHE_VHOSTS, "$str_dr\n\n" );
}

/**
 * Add second section to httpd-vhosts.conf
 *
 * @param $doc_root (string) 
 * 
 */
function wpdm_add_vhost_entry_2( $doc_root ) {
    if( WPDM_SERVER_TYPE == 'wpdevmate') {
        $port = '*:@@Port@@';
    } else {
        $port = '*';
    }
    $str_dr = "<VirtualHost $port>\n";  
    $str_dr .= "      DocumentRoot \"$doc_root\"\n";
    $str_dr .= "      ServerName localhost\n"; 
    $str_dr .= "</VirtualHost>\n";

    wpdm_write_to_file( WPDM_APACHE_VHOSTS, "$str_dr\n\n" );
}

/**
 * Add third section to httpd-vhosts.conf [for website]
 *
 * @param $doc_root_website (string) 
 * @param $website_name (string) 
 * 
 */
function wpdm_add_vhost_entry_3( $doc_root_website, $website_name ) {
    if( WPDM_SERVER_TYPE == 'wpdevmate') {
        $port = '*:@@Port@@';
    } else {
        $port = '*';
    }
    $str_web = "#$website_name#\n"; // used as a reference point/anchor so that we can remove later on
    $str_web .= "<VirtualHost $port>\n"; // "<VirtualHost *>
    $str_web .= "      DocumentRoot \"$doc_root_website\"\n";
    $str_web .= "      ServerName $website_name\n"; 
    $str_web .= "   <Directory \"$doc_root_website\">\n";
    $str_web .= "      Options Indexes FollowSymLinks ExecCGI Includes\n";
    $str_web .= "      AllowOverride All\n";
    $str_web .= "      Order allow,deny\n";
    $str_web .= "      Allow from all\n";
    $str_web .= "   </Directory>\n";
    $str_web .= "</VirtualHost>\n";
    $str_web .= "#$website_name#\n";

    // if the windows hosts file exists..
    if( file_exists( WPDM_PATH_TO_HOSTS ) ) {
    } 
    wpdm_write_to_file( WPDM_APACHE_VHOSTS, "$str_web\n" );
}

/**************************************************************************************
* database
**************************************************************************************/

/**
 * Set (mysql\my.ini) the myslli port number as default then return value
 *
 * @param $path_to_myini (string)
 * 
 */
function wpdm_get_set_mysql_port( $path_to_myini ) {
    $wpdm_my_ini_settings = parse_ini_file( $path_to_myini, 'my-function' );
    $my_sql_port =  $wpdm_my_ini_settings['mysqld']['port'];
    ini_set( 'mysqli.default_port', $my_sql_port );
    $wpdm_mysql_port_no = ini_get( 'mysqli.default_port' );

    return $wpdm_mysql_port_no; 
}

/**
 * Check connection to database
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * 
 */
function wpdm_db_check_connection( $db_host, $db_user, $db_pass ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, '', $port ); //$bDatabase - optional
        // Check connection
        if ( mysqli_connect_errno() ) {
          //error_reporting(0);
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
          return false;
        } else {
            return true;
        }
        mysqli_close( $connection );
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Backup tables in a database and export results to a file called 'database.sql'
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * 
 */
function wpdm_db_backup_tables( $db_host, $db_user, $db_pass, $db_name, $tables = '*' ) {   
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port ); 
        mysqli_select_db( $connection, $db_name );
        mysqli_query( $connection, "SET NAMES 'utf8'" );
        
        if ( $tables == '*' ) { //get all of the tables
            $tables = array();
            $result = mysqli_query( $connection, 'SHOW TABLES' ) or die( mysqli_error( $result ) ); 
            
            while ( $row = mysqli_fetch_row( $result ) ) {
                $tables[] = $row[0];
            }
        } else {
            $tables = is_array( $tables ) ? $tables : explode( ',', $tables );
        }
        // add information
        $return = '';
        
        foreach ( $tables as $table ) { //cycle through
            $result = mysqli_query( $connection, 'SELECT * FROM ' . $table ) or die( mysqli_error( $result ) );
            $num_fields = mysqli_field_count( $connection );
            
            $return .= 'DROP TABLE ' . $table . ';';
            $row2 = mysqli_fetch_row( mysqli_query( $connection, 'SHOW CREATE TABLE ' . $table ) ); 
            $return .= "\n\n" . $row2[1] . ";\n\n";
            
            for ( $i = 0; $i < $num_fields; $i++ ) {
                while ( $row = mysqli_fetch_row( $result ) ) {
                    $return .= 'INSERT INTO ' . $table . ' VALUES(';
                    for ( $j = 0; $j < $num_fields; $j++ ) {
                        $row[$j] = addslashes( $row[$j] );
                        $row[$j] = str_replace( "\n", "\\n", $row[$j] );
                        if ( isset( $row[$j] ) ) {
                            $return .= '"' . $row[$j] . '"';
                        } else {
                            $return .= '""';
                        }
                        if ( $j < ( $num_fields - 1) ) {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
            }
            $return .= "\n\n\n";
        }
        $handle = fopen( 'database' . '.sql', 'w+' );
        fwrite( $handle, $return );
        fclose( $handle );

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Import a database [from a .sql file]
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $file_name (string) 
 * 
 */
function wpdm_db_import( $db_host, $db_user, $db_pass, $db_name, $file_name ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $query = "";
        $lines    = file( $file_name );
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( $line != "" && substr( $line, 0, 2 ) != '--' ) {
                $query .= $line;
                if ( substr( $line, -1 ) == ';' ) {
                    mysqli_query( $connection, $query );
                    //echo ($query."\n"); // output to screen
                    if ( mysqli_error( $connection ) ) {
                        echo ( "\n". mysqli_error( $connection ) . ": " );
                        echo ( mysqli_error( $connection ) ."\n" );
                    }
                $query = "";
                }
            }
        }
        unset ( $line );
        if ( mysqli_error( $connection ) ) 
        {
            echo ( mysqli_error( $connection ) . ": " );
            echo ( mysqli_error( $connection ) );
        }
        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Get a list of tables from a database
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * 
 */
function wpdm_db_get_tables( $db_name, $db_host, $db_user, $db_pass ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port );
        
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $tables          = array();
        $list_tables_sql = "SHOW TABLES FROM {$db_name};";
        $result          = mysqli_query( $connection, $list_tables_sql );
        
        if ( $result ) {
            while ( $table  = mysqli_fetch_row( $result ) ) {
                $tables[]   = $table[0];
            }
        }
        return $tables;

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Drop a database table
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $tables (array)
 * 
 */
function wpdm_db_drop_tables( $db_name, $db_host, $db_user, $db_pass, Array $tables ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port );
        
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        foreach ( $tables as $table ) {
            $sql = 'DROP TABLE IF EXISTS `' . $table . '`';
            //echo "table= " . $table;
            if ( mysqli_query( $connection, $sql ) ) {
                //echo "Tables successfully dropped\n";
            } else {
                echo 'Error dropping tables: ' . mysqli_error( $connection ) . "\n";
            }
        }
        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Delete record from a database
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $query (string) 
 * 
 */
function wpdm_db_delete_record( $db_host, $db_user, $db_pass, $db_name, $query ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port ); //$bDatabase - optional
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $sql = $query; 

        if ( mysqli_query( $connection, $sql ) ) {
          echo "Record deleted successfully";
        } else {
          echo "Error deleting record: " . mysqli_error($connection);
        }

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Drop a database
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $website (string) 
 * 
 */
function wpdm_db_drop( $db_host, $db_user, $db_pass, $website ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, '', $port ); 
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $sql = 'DROP DATABASE `' . $website . '`';

        if ( mysqli_query( $connection, $sql ) ) {
        } else {
            echo 'Error dropping database: ' . mysqli_connect_error() . "\n";
        }

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Update a record in a database
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $query (string) 
 * 
 */
function wpdm_db_update_record( $db_host, $db_user, $db_pass, $db_name, $query ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port ); //$bDatabase - optional
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $sql = $query; 
        if ( mysqli_query( $connection, $sql ) ) {
          echo "Record updated successfully. <br/>";
        } else {
          echo "Error updating record: " . mysqli_error( $connection );
        }

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * return the siteurl / home from options table
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $query (string) 
 * 
 */
function wpdm_db_return_home_siteurl( $db_host, $db_user, $db_pass, $db_name, $query ) {
    try{
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port ); 
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $sql    = $query;
        $result = mysqli_query( $connection, $sql );
        $row    = mysqli_fetch_assoc( $result );
        //echo $row["option_value"];
        // Free result set
        mysqli_free_result( $result );
        mysqli_close( $connection );

        return $row['option_value'];

        } catch ( Exception $e ) {
            echo 'Caught exception: ' . $e->getMessage();
        }
}

/**
 * Select database and display all results
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $query (string) 
 * 
 */
function wpdm_db_select( $db_host, $db_user, $db_pass, $db_name, $query ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port ); 
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $result = mysqli_query( $connection, $query );
        $array  = array();
        while( $row = mysqli_fetch_array( $result ) ) {
            $array[] = $row;
              }
        return $array;

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Check if a database exists
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * 
 */
function wpdm_db_does_it_exist( $db_host, $db_user, $db_pass, $db_name ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, '', $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        if( mysqli_select_db( $connection, $db_name ) == false ) {
        //if ( $connection->select_db( $db_name ) == false ) {
            return false;
        } else {
            return true;
        }
        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Check if a database user exists
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * 
 */
function wpdm_db_does_user_exist( $db_user, $db_pass, $db_host ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, '', $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
            error_reporting( 0 );
            //echo "Failed to connect to MySQL: " . mysqli_connect_error();
            return false;
        } else {
            return true;
        }
        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Check if a record [row] exists
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $query (string) 
 * 
 */
function wpdm_db_does_record_exist( $db_user, $db_pass, $db_host, $db_name, $query ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        } else {
        }
        if ( $result = mysqli_query( $connection, $query ) ) {

            // Return the number of rows in result set
            $rowcount = mysqli_num_rows( $result );
             //printf( "Result set has %d rows.\n", $rowcount );
            // Free result set
            mysqli_free_result( $result );
        }
        mysqli_close( $connection );

        return $rowcount;

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Add tables to a database
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $query (string) 
 * 
 */
function wpdm_db_add_tables( $db_user, $db_pass, $db_host, $db_name, $query ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        // Create table
        $sql = $query; 

        if ( mysqli_query( $connection, $sql ) ) {
        } else {
          echo "Error creating table: " . mysqli_error( $connection );
        }

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Create a mysql database
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * 
 */
function wpdm_db_create( $db_user, $db_pass, $db_host, $db_name ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, '', $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        //$sql = "CREATE DATABASE IF NOT EXISTS `$db_name`";
        $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
        
        if ( mysqli_query( $connection, $sql ) ) {
        } else {
            echo "Error creating database $db_name: " . mysqli_error( $connection );
        }

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Insert data into a database
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $query (string) 
 * 
 */
function wpdm_db_insert_data( $db_host, $db_user, $db_pass, $db_name, $query ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        $sql = $query;
        
        if ( mysqli_query( $connection, $sql ) ) {
        } else {
            echo "Error importing data: " . mysqli_error( $connection );
        }

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Create a database user
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $database_user (string) 
 * @param $database_pass (string) 
 * 
 */
function wpdm_db_add_user( $db_user, $db_pass, $db_host, $database_user, $database_pass ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, '', $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $sql = "CREATE user $database_user@localhost IDENTIFIED BY '$database_pass';";
        $sql = "GRANT ALL PRIVILEGES ON *.* TO $database_user@localhost IDENTIFIED BY '$database_pass';";
       
        if ( mysqli_query( $connection, $sql ) ) {
        } else {
            echo "Error creating user $database_user: " . mysqli_error( $connection );
        }

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Drop a database user
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * 
 */
function wpdm_db_drop_user( $db_host, $db_user, $db_pass, $drop_user ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, '', $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        $sql = "DROP USER $drop_user@localhost;";

        if ( mysqli_query( $connection, $sql ) ) {
        } else {
            echo "Error dropping user: $drop_user.  Error " . mysqli_error( $connection ) . "\n";
        }
        
        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Database general command
 *
 * @param $db_host (string) 
 * @param $db_user (string) 
 * @param $db_pass (string) 
 * @param $db_name (string) 
 * @param $query (array)
 * 
 */
function wpdm_db_command( $db_host, $db_user, $db_pass, $db_name, Array $query ) {
    try {
        $port = wpdm_get_set_mysql_port( WPDM_MYSQL_CONFIG );
        $connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $port );
        // Check connection
        if ( mysqli_connect_errno() ) {
          echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        foreach ( $query as $queries ) {
            $sql = $queries;
            
            if ( mysqli_query( $connection, $sql ) ) {
            } else {
                echo 'Error dropping tables: ' . mysqli_error( $connection ) . "\n";
            }
        }

        mysqli_close( $connection );

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**************************************************************************************
* wordpress
**************************************************************************************/

/**
 * Get variables from the wp-config file
 *
 * @param $path_to_file (string) 
 * 
 */
function wpdm_wordpress_get_wpconfig_info( $path_to_file ) {
    try {
        if( file_exists( $path_to_file ) ) {
            if ( !$fh = fopen( $path_to_file, 'r' ) ) {
                die( "Cannot open wp-config.php." );
                echo "Cannot open wp-config.php.";
            }
            // http://www.phpro.org/tutorials/Introduction-to-PHP-Regex.html
            $wpc_WPDM_DB_PASSWORD = ""; // in case the password is empty
            while ( !feof( $fh ) ) {
                $line = fgets( $fh );
                if  (preg_match( '/^\s*define\s*\(\s*\'DB_NAME\'\s*,\s*\'(.+?)\'/', $line, $match ) ) {
                    $wpc_DB_NAME = $match[1];
                } elseif ( preg_match( '/^\s*define\s*\(\s*\'DB_USER\'\s*,\s*\'(.+?)\'/', $line, $match ) ) {
                    $wpc_WPDM_DB_USER = $match[1];
                } elseif ( preg_match( '/^\s*define\s*\(\s*\'DB_PASSWORD\'\s*,\s*\'(.+?)\'/', $line, $match ) ) {
                    $wpc_WPDM_DB_PASSWORD = $match[1];
                } elseif ( preg_match( '/^\s*define\s*\(\s*\'DB_HOST\'\s*,\s*\'(.+?)\'/', $line, $match ) ) {
                    $wpc_WPDM_DB_HOST = $match[1];
                } elseif ( preg_match( '/^\s*\$table_prefix\s*=\s*\'(.+?)\'/', $line, $match ) ) {
                    $wpc_TABLE_PREFIX = $match[1];
                }
            } //end while

            fclose( $fh );

            // check if file is empty
            if ( !0 == filesize( $path_to_file ) ) {
                return array( $wpc_DB_NAME, $wpc_WPDM_DB_USER, $wpc_WPDM_DB_PASSWORD, $wpc_WPDM_DB_HOST, $wpc_TABLE_PREFIX );
            }
        }
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * wpdm_wordpress_get_latest_version_url function to get the latest version download url
 * 
 */
function wpdm_wordpress_get_latest_version_url() {
    try {
        // check if cURL is enabled
        if( wpdm_check_curl_enabled() == false) {
            echo '<p><strong>cURL</strong> is NOT installed or enabled, and this script needs cURL to work.<br/> 
            Please read the README file for further help on how to fix this.</p>';
            die();
        }
        $url = 'http://api.wordpress.org/core/version-check/1.6/';
        $c   = curl_init( $url );
        curl_setopt( $c, CURLOPT_RETURNTRANSFER, 1 );
        $page = curl_exec( $c );
        curl_close( $c );
        $ret = unserialize( $page );
        /*  echo '<pre>';
        echo $local;
        var_dump($ret);
        echo '</pre>';
        die();*/
        return $ret['offers'][0]['download'];
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Get the latest version number form WordPress
 * 
 */
function wpdm_wordpress_get_version() {
    try {
        $wp_version = wpdm_wordpress_get_latest_version_url();
        $wp = str_replace( "wordpress-", "", basename( $wp_version, ".zip" ) . PHP_EOL );
        return $wp;
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Get the latest version number form WordPress folder
 * 
 */
function wpdm_get_wp_version( $path_to_file ) { //  $wp_version = '3.9.2'; wp-includes/version.php.
    try {
        if( file_exists( $path_to_file ) ) 
        {
            if ( !$fh = fopen( $path_to_file, 'r' ) ) {
                die( "Cannot open wp-config.php." );
                echo "Cannot open wp-config.php.";
            }
            while ( !feof( $fh ) ) {
                $line = fgets( $fh );

                if ( preg_match( '/^\s*\$wp_version\s*=\s*\'(.+?)\'/', $line, $match ) ) {
                    $wpc_WP_VERSION = $match[1];
                }
            } 
            fclose( $fh );
            return $wpc_WP_VERSION;
        } else {
            echo '';
        }
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * wpdm_wordpress_get_latest_version_url function to get the latest version download url
 * 
 */
function wpdm_wordpress_get_salt() {
    try {
    // check if cURL is enabled
    if( wpdm_check_curl_enabled() == false) {
        echo '<p><strong>cURL</strong> is NOT installed or enabled, and this script needs cURL to work.<br/> 
        Please read the README file for further help on how to fix this.</p>';
        die();
    }
    $url = 'https://api.wordpress.org/secret-key/1.1/salt/';
    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_URL, $url);
    curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);

    $jsonData = json_decode(curl_exec($curlSession));
    curl_close($curlSession);

    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**************************************************************************************
* misc
**************************************************************************************/

/**
 * wpdm_include_jquery simple function to include jQuery js
 * if not connected to the internet, use the local version
 * 
 */
function wpdm_include_jquery() {
    try {
        if( wpdm_check_internet_connection( 'www.google.com' ) ) {
            $src = "http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js";
        } else {
            $src = "resources/scripts/jquery.min.js";
        }
        ?>
          <script type="text/javascript" src="<?php echo $src; ?>"></script>
          <?php
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Display a message to close the window
 *
 * @param $message (string) 
 * 
 */
function wpdm_close_window( $message ) {
    try {
        echo $message . "<br/>";
        echo "You can " . "<a href=\"javascript:window.open('','_self').close();\">close this page </a> now.<br/>";
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**
 * Display a popup javascript message box
 *
 * @param $message (string) 
 * 
 */
function wpdm_show_messagebox( $message ) {
    try {
        return "<SCRIPT>alert('$message');</SCRIPT>";
    } catch ( Exception $e ) {
        echo 'Caught exception: ' . $e->getMessage();
    }
}

/**************************************************************************************
* get system info (browser, operating system etc.)
**************************************************************************************/

$user_agent = $_SERVER['HTTP_USER_AGENT'];

/**
 * Get operating system type [win, mac, linux etc]
 * 
 */
function wpdm_get_os_type() { 

    global $user_agent;

    $os_platform_type   = "Unknown OS Platform";
    $os_array           = array(
                        '/windows nt 6.2/i'     =>  'Windows',
                        '/windows nt 6.1/i'     =>  'Windows',
                        '/windows nt 6.0/i'     =>  'Windows',
                        '/windows nt 5.2/i'     =>  'Windows',
                        '/windows nt 5.1/i'     =>  'Windows',
                        '/windows xp/i'         =>  'Windows',
                        '/windows nt 5.0/i'     =>  'Windows',
                        '/windows me/i'         =>  'Windows',
                        '/win98/i'              =>  'Windows',
                        '/win95/i'              =>  'Windows',
                        '/win16/i'              =>  'Windows',
                        '/macintosh|mac os x/i' =>  'Mac',
                        '/mac_powerpc/i'        =>  'Mac',
                        '/linux/i'              =>  'Linux',
                        '/ubuntu/i'             =>  'Ubuntu',
                        '/iphone/i'             =>  'iPhone',
                        '/ipod/i'               =>  'iPod',
                        '/ipad/i'               =>  'iPad',
                        '/android/i'            =>  'Android',
                        '/blackberry/i'         =>  'BlackBerry',
                        '/webos/i'              =>  'Mobile'
                        );

    foreach ( $os_array as $regex => $value ) { 
        if ( preg_match( $regex, $user_agent ) ) {
            $os_platform_type = $value;
        }
    }   
    return $os_platform_type;
}

/**
 * Get operating type and version
 * 
 */
function wpdm_get_os_version() { 

    global $user_agent;

    $os_platform    = "Unknown OS Platform";

    $os_array       = array(
                    '/windows nt 6.2/i'     =>  'Windows 8',
                    '/windows nt 6.1/i'     =>  'Windows 7',
                    '/windows nt 6.0/i'     =>  'Windows Vista',
                    '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                    '/windows nt 5.1/i'     =>  'Windows XP',
                    '/windows xp/i'         =>  'Windows XP',
                    '/windows nt 5.0/i'     =>  'Windows 2000',
                    '/windows me/i'         =>  'Windows ME',
                    '/win98/i'              =>  'Windows 98',
                    '/win95/i'              =>  'Windows 95',
                    '/win16/i'              =>  'Windows 3.11',
                    '/macintosh|mac os x/i' =>  'Mac OS X',
                    '/mac_powerpc/i'        =>  'Mac OS 9',
                    '/linux/i'              =>  'Linux',
                    '/ubuntu/i'             =>  'Ubuntu',
                    '/iphone/i'             =>  'iPhone',
                    '/ipod/i'               =>  'iPod',
                    '/ipad/i'               =>  'iPad',
                    '/android/i'            =>  'Android',
                    '/blackberry/i'         =>  'BlackBerry',
                    '/webos/i'              =>  'Mobile'
                        );

    foreach ( $os_array as $regex => $value ) { 
        if ( preg_match( $regex, $user_agent ) ) {
            $os_platform = $value;
        }
    }   
    return $os_platform;
}

/**
 * Get the users browser
 * 
 */
function wpdm_get_browser_type() {

    global $user_agent;

    $browser        = "Unknown Browser";
    $browser_array  = array(
                    '/msie/i'       =>  'Internet Explorer',
                    '/firefox/i'    =>  'Firefox',
                    '/safari/i'     =>  'Safari',
                    '/chrome/i'     =>  'Chrome',
                    '/opera/i'      =>  'Opera',
                    '/netscape/i'   =>  'Netscape',
                    '/maxthon/i'    =>  'Maxthon',
                    '/konqueror/i'  =>  'Konqueror',
                    '/mobile/i'     =>  'Handheld Browser'
                        );

    foreach ( $browser_array as $regex => $value ) { 
        if ( preg_match( $regex, $user_agent ) ) {
            $browser = $value;
        }
    }
    return $browser;
}

/**
 * Generate a random string 
 * (used to get salt so we dont need to connect to the internet)
 *
 * @param $length (int) 
 * 
 */

function wpdm_random_string( $length ) { // random key generator
    $secret_ar = str_split("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%^&*()-=+[]{};:<>,.?");
    $secret = '';
    for( $i=0; $i < $length; $i++ ){
        $secret .= $secret_ar[rand( 0,85 )];
    }
    //return substr( $secret,0,64 );
    return substr( $secret,0,$length );
}

/**
 * Removes the drive letter from a directory string
 *
 * 
 */
function wpdm_remove_drive_letter() {
    $path_in_pieces = explode( '/', $_SERVER['DOCUMENT_ROOT'] );
    $main_folder    = $path_in_pieces[1];
    $prog_dir       = "\\" . $main_folder;
    return $prog_dir;
}

/**
 * Return document root [includes drive letter]
 *
 * 
 */
function wpdm_get_document_root() {
$doc_root = realpath( $_SERVER['DOCUMENT_ROOT'] );
// strip drive letter if found
if( strpos( $doc_root, ':' ) === 1 ) {
    $doc_root = substr( $doc_root, 2 );
}
return str_replace( '\\', '/', $doc_root );
}

/**
 * Return the full path of the wpdm directory
 * Strip Drive letter
 * 
 */
function wpdm_get_wpdm_directory() {
    $dir_include = realpath( dirname( __FILE__ ) );
    // strip drive letter if found
    if( strpos( $dir_include, ':' ) === 1 ) {
        $dir_include = substr( $dir_include, 2 );
    }
    return str_replace( '\\', '/', $dir_include );
}

?>