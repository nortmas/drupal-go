#!/bin/bash
#
# Clean the build.
#

BUILD_DIR="$WORKING_DIR/$BRANCH"

# Sometimes, the job is launched as root user, fix the owner in such case.
if [ "$USER" == "root" ]; then
    chown -R gitlab-runner:gitlab-runner "$BUILD_DIR";
fi