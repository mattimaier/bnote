#!/bin/sh
## BUILD FILE FOR BNote Releases

# Check that the release name is given
if [ "$1" == "" ]; then
	echo "Usage: build.bash <release_number>";
	exit -1;
fi
echo "Building Release $1 ..."

# Settings
root_dir=".."
main_dir="../BNote"
app_dir="../BNote-App"
tmp_dir="tmp/"

# Create temporary directory
if [ ! -d "$tmp_dir" ]; then
	mkdir $tmp_dir;
else
	rm -r $tmp_dir/*;
fi


## ROOT RESOURCES
echo "Preparing root resources..."
# copy level 1 resources
cp "$root_dir/index.php" $tmp_dir
cp -r "$root_dir/resources" $tmp_dir

## APP
echo "Preparing app..."
# copy app
cp -rv $app_dir $tmp_dir

# clean up in target folder
rm -r "$tmp_dir/BNote-App/data"
rm "$tmp_dir/BNote-App/.DS_Store"


## DESKTOP
echo "Preparing desktop application..."

# Compile Themes
echo "Compiing themes..."
cd $main_dir/style/css
./compile_themes.sh
cd -

# copy main application
cp -rv $main_dir $tmp_dir

# clean up the main application
tmp_main_dir="$tmp_dir/BNote"

rm "$tmp_main_dir/.buildpath"
rm "$tmp_main_dir/.DS_Store"
rm "$tmp_main_dir/.gitignore"
rm "$tmp_main_dir/.project"
rm -r "$tmp_main_dir/.settings"
rm -r "$tmp_main_dir/devel"

rm "$tmp_main_dir/config/company.xml"
rm "$tmp_main_dir/config/config.xml"
rm "$tmp_main_dir/config/database.xml"

rm "$tmp_main_dir/data/members/*.pdf"
rm "$tmp_main_dir/data/programs/*.pdf"
rm -r "$tmp_main_dir/data/share/groups/*"  # create on installation
rm -r "$tmp_main_dir/data/share/members/*"  # create on installation
rm -r "$tmp_main_dir/data/webpages/*"  # no webpages are shipped


## Finalize
echo "Creating zip file..."
target="bnote_release_$1.zip"
zip -r $target $tmp_dir/*

echo "$target created. Done."
