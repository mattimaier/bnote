import sys
import os
from shutil import copyfile


def replace_in_file(filename, search, replace):
    print("Replacing: " + search + " for " + replace + " in " + filename)

    # Read in the file
    with open(filename, 'r') as file:
        filedata = file.read()

    # Replace the target string
    filedata = filedata.replace(search, replace)

    # Write the file out again
    with open(filename, 'w') as file:
        file.write(filedata)


def read_colors(color_filename, color_array):
    with open(color_filename, 'r') as file:
        for line in file:
            color_array.append(line.strip())


def replace_all_colors(filename, theme_colors, default_colors):
    for i in range(0, len(default_colors)):
        replace_in_file(filename, default_colors[i], theme_colors[i])


if __name__ == '__main__':
    # validate input
    if len(sys.argv) == 0:
        print("Please specify the name of the theme as first parameter.")
        exit(1)

    # init theme
    theme_name = sys.argv[1]
    theme_color_filename = theme_name + ".txt"

    # check color definition existence of theme
    if not os.path.isfile(theme_color_filename):
        print("Unable to read " + theme_color_filename + ". Please make sure it exists.")
        exit(2)

    # read colors
    theme_colors = []
    read_colors(theme_color_filename, theme_colors)
    default_colors = []
    read_colors('default.txt', default_colors)

    if len(theme_colors) != len(default_colors):
        print("Please make sure the theme colors are as many as the default colors.")
        exit(3)

    # create directory
    os.mkdir(theme_name)

    # copy files from default to theme directory
    for filename in os.listdir("default/"):
        copyfile("default/" + filename, theme_name + "/" + filename)

    # replace colors in files
    for filename in os.listdir(theme_name):
        if filename.endswith(".less"):
            replace_all_colors(theme_name + "/" + filename, theme_colors, default_colors)
