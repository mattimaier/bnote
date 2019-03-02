tmp_dir="BNote-Releases/tmp"

# Check if the first argument is set
if [ -z $1 ]; then
 echo 'usage: build_patch.sh release-commit'
fi

# Change to root dir of BNote Repo
cd ..

# Compile Themes
cd BNote/style/css
./compile_themes.sh
cd -

# Check if the tmp folder exists -> if so, clean it otherwise create it
if [ -d "$tmp_dir" ]; then
 rm -r $tmp_dir/*
else
 mkdir $tmp_dir
fi

# Copy changed files
git diff -z --name-only HEAD $1 | xargs -0 -IREPLACE rsync -aR REPLACE $tmp_dir

# Clean Mac stuff
find $tmp_dir -name .DS_Store -delete

# Create zip file
zip -r BNote-Releases/BNote/Patches/bnote_latest_patch.zip $tmp_dir


