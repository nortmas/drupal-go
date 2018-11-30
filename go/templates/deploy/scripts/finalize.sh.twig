#!/bin/bash
#
# Clean up.
#

# Set variables.
BUILD_DIR="$ARTIFACT_DIR/$BRANCH"

# Remove the archive with artifact.
echo rm "$ARTIFACT_DIR/$BRANCH.tgz"
rm "$ARTIFACT_DIR/$BRANCH.tgz"

# Remove private key.
rm ~/.ssh/stage_private_key

# Sometimes, the job is launched as root user, fix the owner in such case.
if [ "$USER" == "root" ]; then
    chown -R gitlab-runner:gitlab-runner "$BUILD_DIR";
fi