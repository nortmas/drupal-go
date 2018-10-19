#!/bin/bash
#
# Prepare the acquia repo.
#
# NB: do not change directory here, this could break other scripts.
#
echo "Starting $0: prepare the structure"

BUILD_DIR="$WORKING_DIR/repo-$BRANCH"

# Initialize the working directory.
#if [ -d "$BUILD_DIR" ]; then
#    rm -rf "$BUILD_DIR/*"
#else
#    mkdir -p "$BUILD_DIR";
#fi

cd $BUILD_DIR
echo "Change directory to $BUILD_DIR"
#echo "Enable the maintenance mode."
#make drush arg="sset system.maintenance_mode 1"

rm -rf "$BUILD_DIR/vendor"
rm -rf "$BUILD_DIR/web/core"
rm -rf "$BUILD_DIR/web/libraries"
rm -rf "$BUILD_DIR/web/modules"
rm -rf "$BUILD_DIR/web/profiles"
rm -rf "$BUILD_DIR/web/themes/contrib"