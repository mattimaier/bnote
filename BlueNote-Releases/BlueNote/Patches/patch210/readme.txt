# PATCH for BlueNote
# BlueNote Version 2
# Patch Version: 01

# How to install patch?

1. Copy the file src/data/database.php out of your patch folder to the same location in the bluenote folder. 
2. Copy update_db.php to the root folder of your bluenote application.
3. Execute the update_db.php script.
4. Remove update_db.php from the root folder of your bluenote application.
5. Update config.xml from the config/ folder in the patch -> make sure you don't override your current config.xml. Just add the new attributes.
6. Copy all files from the patch to the bluenote folder except config/, update_db.php, readme.txt and release_notes.txt.
