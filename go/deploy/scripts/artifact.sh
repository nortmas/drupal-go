#!/bin/bash
#
# Build the artifact.
#

# Set variables.
BUILD_DIR="$ARTIFACT_DIR/$BRANCH"
MODULES_DIR="$BUILD_DIR/web/modules/"
LIBRARIES_DIR="$BUILD_DIR/web/libraries/"

# Sometimes, the job is launched as root user.
if [ "$USER" != "gitlab-runner" ]; then echo "WARNING: this job was launched with user '$USER'"; fi

# Check mandatory variables.
if [ -z "$ARTIFACT_DIR" ]; then echo "ARTIFACT_DIR is unset"; exit 33; else echo "ARTIFACT_DIR is set to '$ARTIFACT_DIR'"; fi

# Clean up or initialize the working directory.
if [ -d "$BUILD_DIR" ]; then
    rm -rf $BUILD_DIR
fi

mkdir -p $BUILD_DIR

# Create the artifact.
echo rsync -a --del --exclude-from "$CI_PROJECT_DIR/deploy/.rsync-artifact-exclude" "$CI_PROJECT_DIR/" "$BUILD_DIR/"
rsync -a --del --exclude-from "$CI_PROJECT_DIR/deploy/.rsync-artifact-exclude" "$CI_PROJECT_DIR/" "$BUILD_DIR/"

# Remove .git from contrib modules.
if [ -d "$MODULES_DIR" ]; then
    find "$MODULES_DIR" -name .git -print0 | xargs -0 rm -rf
fi

# Remove .git from libraries.
if [ -d "$LIBRARIES_DIR" ]; then
    find "$LIBRARIES_DIR" -name .git -print0 | xargs -0 rm -rf
fi

# Get into the artifact directory.
echo cd $BUILD_DIR
cd $BUILD_DIR

# Create the archive with artifact.
echo tar -zcf $ARTIFACT_DIR/$BRANCH.tgz ./
tar -zcf $ARTIFACT_DIR/$BRANCH.tgz ./