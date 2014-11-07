![Alt text](http://www.wpdevmate.com/wp-content/themes/wpdevmate/images/wpdevmate-login-logo.png "WPDevMate")

Copyright (C) WPDevMate - All Rights Reserved - 
http://www.wpdevmate.com

https://github.com/lucidpixel/WPDevMate


CORE-FILES VERSION: 1.05 (Download Core files from https://github.com/lucidpixel/WPDevMate)

SERVER VERSION: 1.0.0.0 (Download WPDevMate WAMP Server from http://www.wpdevmate.com)


WHAT IS WPDEVMATE?
===================

WordPress Developer's Mate (WPDevMate) is a Cross Platform set of php scripts that are run from a central location to aid web developers with WordPress development (installs, backups and deployments etc.). 

WPDevMate is a companion for Web designers and Developers that use WordPress on a regular basis; who spend valuable time in installing, configuring and deploying WordPress many times over; and want to cut their time down significantly by having a set of tools in one location that can do all the hard work for them. 

------------

DEVELOPMENT
==================

WPDevMate has been tested in a Windows 7 environment using various servers (XAMPP, Server2Go, USBWebServer) with no issues and has been developed to work independantly of the Operating system environment (core files only). 

------------

INFORMATION
=====================

All actions should be run from the main admin interface (index.php).

In order to run WPDevMate successfully, you will need to connect to the internet so that WPDevMate can download the latest version of WordPress automatically. Once you do this, there is no need to connect again unless you want to download a major release, a 'nightly' version, or you want to upload an archive to a remote location via FTP. As soon as the latest version has been downloaded you can start using WPDevMate.

For further information on how to use WPDevMate, please read the Frequently asked Questions section at http://wpdevmate.com/faq/

------------

HOW TO INSTALL
======================

WPDevMate is cross platform compatible. However, there are two versions: One that contains a standalone portable server that is run on Windows Operating system; and the other that contains the necessary core files that can be run in your own server environment (WAMP, MAMP etc.).

------------

WPDevMate Server 
----------------------------

Portable stand-alone server - downloaded from http://www.wpdevmate.com


1). Unzip the file WPDevMate.zip to the root of your drive (or USB drive).

2). Open includes/config.ini and make any necessary changes.

3). Start WPDevMate.exe anc click 'Turn On'.

4). Click 'Open HTTP'.

5). Click on the 'Continue to WPDevMate' link.

------------


WPDevMate files only
------------------------------

Source files - downloaded from https://github.com/lucidpixel/WPDevMate


You must have cURL enabled. Read the PREREQUISITS section for further information and other pre-requisites.

1). Extract the wpdevmate-x.xx.zip to your server root (the directory where your websites are run from (e.g. /htdocs/wpdevmate/))

2). Open includes/config.ini.php and enter values that match your environment. 
    
3). Visit your Server URL (http://127.0.0.1/) and open /wpdevmate/index.php (depending on your server environment, you may need to edit your apache conf files)

When WPDevMate runs for the very first time it needs to connect to the internet to download the latest version of WordPress, so please be patient. As long as the file wordpress-latest.zip is present in the wpdevmate directory, it will not need to do this again.


------------

PREREQUISITS
=======================

The portable standalone version of WPDevMate is ready to run, so wont need to be configured. If you have WPDevMate core files only, please continue reading...


php.ini
--------------------

To run WPDevMate, you MUST have cURL enabled on your server and also PDO mySQL extensions. To enable, open your php.ini file (e.g. /php/php.ini) and un-comment (remove the simi-colon ';' from the begining of the line):

    ;extension=php_curl.dll
    ;extension=php_pdo_mysql.dll


Once the changes have been made to your php.ini file, save the file and restart your web server.



httpd.conf
--------------------

To enable virtual hosts and friendly URLs etc, you will need to open your 'httpd.conf' file (e.g. /servername/apache/conf/httpd.conf) 
and un-comment the following (remove the hash '#' from the begining of the line):

    #Include "conf/extra/httpd-vhosts.conf"
    #LoadModule rewrite_module modules/mod_rewrite.so
    #LoadModule vhost_alias_module modules/mod_vhost_alias.so


config.ini
--------------------

WPDevMate has been developed so that the scripts require minimum intervention or customisation. A config file (includes/config.ini) has been created that should be customised to fit your environment. This is the only script that needs to be edited. 



------------


FEATURES
==============================


* Install WordPress. Either download the latest version or a nightly build of WordPress, or install your own custom build (with included plugins, settings etc.). 

* One Click install - allows you to install WordPress in less than 20 seconds! 

* Add your own custom installs (configured with settings, plugins) as a base for future projects.

* Backup websites.

* Remove Websites and Drop associated database.

* Create Virtual hosts. 

* Import other WordPress websites. **

* Upload websites to server via FTP / sFTP ready for deployment. Installer file (installer.php) is derived from the WordPress Duplicator Plugin (by Cory Lamle) that has been configured to run without having to login to WordPress. Why change something that works perfectly already!

* Compatability checks to see that your server has the necessary components needed to install and run WordPress.

**NOTE**:
To import WordPress websites into WPDevMate, you will now need to do this manually (for the moment) due to MAXPATHLEN limitations on Windows.
When extracting a WordPress zip archive, some of the file paths were just too long. For more information, read here http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx#maxpath 

We strongly recommend using the Duplicator plugin to backup your REMOTE websites.



------------


CREDITS
=================

WordPress Duplicator Plugin by Cory Lamle (http://lifeinthegrid.com) -- http://wordpress.org/plugins/duplicator/

phpSEClib - PHP Secure Communications Library (http://phpseclib.sourceforge.net)
