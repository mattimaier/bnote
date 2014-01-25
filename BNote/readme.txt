# BNote
# by Matti Maier Internet Solutions
# www.mattimaier.de

# Release Version 2.4.3
# Release Date 2014-01-25
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

How to update an existing BNote instance?
-----------------------------------------
1. Copy all files (including hidden ones like .htaccess files) from this folder, except:
	- all files from the config/ folder including the folder itself
	- readme.txt
	- release_notes.txt