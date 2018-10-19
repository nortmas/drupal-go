#!/bin/bash
#
# Build acquia artifacts.
#
# NB: do not change directory here, this could break other scripts.
#
echo "Starting $0: Build artifacts"

# Build an artifact in the working folder.
BUILD_DIR="$WORKING_DIR/repo-$BRANCH"
FILES_DIR="$BUILD_DIR/web/sites/default/files"
MODULES_DIR="$BUILD_DIR/web/modules/"
LIBRARIES_DIR="$BUILD_DIR/web/libraries/"

# Synchronize the root folder and exclude emulsify theme.
rsync -a --del --exclude-from "$CI_PROJECT_DIR/.gitlab-ci.rsync-exclude" "$CI_PROJECT_DIR/" "$BUILD_DIR/"

cp -n "$CI_PROJECT_DIR/.env.$BRANCH.def" "$BUILD_DIR/.env"
cp -n "$CI_PROJECT_DIR/docker-compose.$BRANCH.yml" "$BUILD_DIR/docker-compose.override.yml"

# Synchronize theme separately because we need to avoid emulsify container falling down.
rsync -a --del --exclude "dist" --exclude "node_modules" --exclude "pattern-lab" "$CI_PROJECT_DIR/web/themes/custom/beg/" "$BUILD_DIR/web/themes/custom/beg/"

# Ignore git submodules.
#echo "web/modules/**/.git" > "$BUILD_DIR/.gitignore"

# Remove git submodules.
if [ -d "$MODULES_DIR" ]; then
    find "$MODULES_DIR" -name .git -print0 | xargs -0 rm -rf
fi

# Remove git libraries.
if [ -d "$LIBRARIES_DIR" ]; then
    find "$LIBRARIES_DIR" -name .git -print0 | xargs -0 rm -rf
fi

# Prepare files directory.
if [ ! -d "$FILES_DIR" ]; then
  mkdir -m 775 "$FILES_DIR";
fi

# Prepare tmp directory.
if [ ! -d "$FILES_DIR/tmp" ]; then
  mkdir -m 775 "$FILES_DIR/tmp";
fi

# Prepare private directory.
if [ ! -d "$FILES_DIR/private" ]; then
  mkdir -m 775 "$FILES_DIR/private";
fi

# Mark this build.
#echo "$CI_COMMIT_SHA" > "$BUILD_DIR/CI_COMMIT_SHA"

cd $BUILD_DIR
echo "Change directory to $BUILD_DIR"
make $BRANCH"_deploy"
#echo "Disable the maintenance mode."
#make drush arg="sset system.maintenance_mode 0"
