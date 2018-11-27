#!/bin/bash
#
# Run behat tests.
#
BUILD_DIR="$ARTIFACT_DIR/$BRANCH"

echo "Change directory to $BUILD_DIR"
cd $BUILD_DIR
echo "Remove previous artifacts."
rm -rf tests/behat/_output/*
echo "Running Behat tests"
make go_run_behat