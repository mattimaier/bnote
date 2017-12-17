#!/bin/bash

# check that the name of the skin is set
if [ -z "$1" ]; then 
	echo "Please set the name of the skin as the first parameter."; 
	exit 1; 
fi

# check if color definition for skin exists
if [ -f "$1.txt" ]; then
	echo "Reading colors from $1.txt"; 
else
	echo "Please create color definition $1.txt"
	exit 2;
fi

# read skin colors
declare -a themecolors
i=0
while IFS='' read -r line; do
	themecolors[$i]="$line"
	((i++))
done < "$1.txt"

# copy default theme and move into dir
cp -r default $1
cd $1

# replace colors one-by-one
y=0
while IFS='' read -r line; do
	new=${themecolors[$y]};
	
	echo color no: $y
	echo replace: $line 
	echo with: $new
	
	printf -v replace_line 's/%s/%s' "$line" "$new"  # -> GIVES WRONG RESULT!!!
	sed -i $replace_line *.css;
	((y++))
done < "../default.txt"

cd ..