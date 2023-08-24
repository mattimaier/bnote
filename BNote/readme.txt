# BNote
# by Matti Maier und Stefan Kreminski
# www.bnote.info
# License GPLv3

Requirements
------------
- Apache HTTPd Webserver with...
	- an accessible host configuration
	- modrewrite
	- htaccess activated (see https://wiki.ubuntuusers.de/Apache/mod_rewrite/ for pointers about modrewrite and htaccess, although specific commands and file paths may vary)
	- at least PHP 7.3 module (PHP 8.x is experimentally supported at the moment) with the xml- and xql-modules
- MySQL or MariaDB Server supporting MySQLi (preferrably mysqlnd) driver


How to install BNote?
---------------------
1. Create a new database user on your database server and give him access to a new (blank) database.
2. Copy all files (including hidden ones like .htaccess files) from this folder to your webserver.
(2.1) If you are installing a 3-digit version like 2.4.2, then make sure to take the last full release and update the files first (copy them over).
3. If you are using Mac OS, Linux, Unix, BSD or system alike make sure the permissions on the files are correct. Here is an overview of how it should be:
	770 config/			with the group being the apache runtime user-group
	755 data/ 			with the group being the apache runtime user-group
	775 data/members	with the group being the apache runtime user-group
	775 data/programs	with the group being the apache runtime user-group
	775 data/share		with the group being the apache runtime user-group
	In addition, make sure your web server does not allow to execute script files like PHP or CGI within the data/ directory.
3. Access your newly created BNote instance. An installation script should come up where you can setup the system.
4. IMPORTANT -> Remove install.php from the document root of your BNote instance!!!


How to update an existing BNote 3.4 instance?
---------------------------------------------
1. Remove these folders:
    - BNote/lang
	- BNote/lib
	- BNote/src
	- BNote/style
2. Copy all files (including hidden ones like .htaccess files) from this folder, except:
	- all files from the config/ folder including the folder itself
	- data/nachrichten.html
3. Execute BNote/update_db.php


Note on Database Installation
-----------------------------
If the database.xml configuration file is present, the database will not be initialized. Therefore remove the file and enter
the configuration parameters manually in the form to activate the installation.
