# BNote
# by Matti Maier Internet Solutions
# www.mattimaier.de

# Release Version 2.4
# Release Date 2013-0x-xx
# License GPLv3

Requirements
------------
- Apache2 Webserver with...
	- an accessible host configuration
	- modrewrite
	- htaccess activated	
- MySQL 5.x Database Server
- preferrably Linux OS


How to install BNote?
---------------------
1. Create a new database user in your MySQL database server and give him access to a new database.
2. Edit BNote_Database_Template.sql and write in the first executed line the name of your newly created database.
3. Execute the BNote_Database_Template.sql script with the correct permissions and database name on your database server.
4. Adapt config/database.xml according to your newly created database and user.
5. Adapt config/company.xml according to your band's name and address. Make sure at least the name and the city is correct.
6. Adapt config/config.xml to match your system requirements. Make sure the URL and the email-address of the administrator is correct.
7. Copy all files (including hidden ones like .htaccess files) from this folder, except readme.txt and release_notes.txt.
8. If you are using Mac OS, Linux, Unix, BSD or system alike make sure the permissions on the files are correct. Here is an overview of how it should be:
	750 config/			with the group being the apache runtime user-group
	755 data/ 			with the group being the apache runtime user-group
	775 data/gallery	recursively, with the group being the apache runtime user-group
	775 data/members	with the group being the apache runtime user-group
	775 data/programs	with the group being the apache runtime user-group
	775 data/share		with the group being the apache runtime user-group
	775 data/gallery	with the group being the apache runtime user-group
	664 data/gallery/*	all files in this folder; with the group being the apache runtime user-group
9. Access your newly created BNote instance.


How to update an existing BNote instance?
-----------------------------------------
1. Copy all files (including hidden ones like .htaccess files) from this folder, except:
	- all files from the config/ folder including the folder itself
	- readme.txt
	- release_notes.txt
2. Open the config/config.xml file in the subdirectory of this folder in an editor of your choice.
   Compare the config.xml file with the config.xml file of your current instance and add the missing tags.