#!/bin/bash
#
# Run content update.
#
BUILD_DIR="$WORKING_DIR/repo-$BRANCH"

echo "Change directory to $BUILD_DIR"
cd $BUILD_DIR
echo "Run content update."
make cupd