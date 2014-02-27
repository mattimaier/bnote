# BNote
# by Matti Maier Internet Solutions
# www.mattimaier.de

# Release Version 2.5.1
# Release Date 2014-xx-xx
# License GPLv3

Requirements
------------
- Apache2 Webserver with...
	- an accessible host configuration
	- modrewrite
	- htaccess activated
	- at least PHP 5.3 module	
- MySQL 5.2+ Database Server
- preferrably Linux OS


How to install BNote?
---------------------
1. Create a new database user in your MySQL database server and give him access to a new database.
2. Copy all files (including hidden ones like .htaccess files) from this folder, except readme.txt and release_notes.txt.
(2.1) If you are installing a 3-digit version like 2.4.2, then make sure to take the last full release and update the files first (copy them over).
3. If you are using Mac OS, Linux, Unix, BSD or system alike make sure the permissions on the files are correct. Here is an overview of how it should be:
	750 config/			with the group being the apache runtime user-group
	755 data/ 			with the group being the apache runtime user-group
	775 data/gallery	recursively, with the group being the apache runtime user-group
	775 data/members	with the group being the apache runtime user-group
	775 data/programs	with the group being the apache runtime user-group
	775 data/share		with the group being the apache runtime user-group
	775 data/gallery	with the group being the apache runtime user-group
	664 data/gallery/*	all files in this folder; with the group being the apache runtime user-group
3. Access your newly created BNote instance. An installation script should come up where you can setup the system.
4. Remove install.php from the document root of your BNote instance.


How to update an existing BNote instance?
-----------------------------------------
1. Copy all files (including hidden ones like .htaccess files) from this folder, except:
	- all files from the config/ folder including the folder itself
	- data/nachrichten.html
	- readme.txt
	- release_notes.txt
2. Execute update_db.php to update your database schema.