#!/bin/bash
#
# Check the context of this build.
#
# Mandatory custom variables definied like this in .gitlab-ci.yml:
#
# NB: do not change directory here, this could break other scripts.

echo "Version informations (gitlab-runner)"
docker version
docker-compose version

# Sometimes, the job is launched as root user.
if [ "$USER" != "gitlab-runner" ]; then echo "WARNING: this job was launched with user '$USER'"; fi

# Check mandatory variables.
if [ -z "$WORKING_DIR" ]; then echo "WORKING_DIR is unset"; exit 33; else echo "WORKING_DIR is set to '$WORKING_DIR'"; fi