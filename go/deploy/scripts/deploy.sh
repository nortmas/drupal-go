#!/bin/bash
#
# Deploy artifacts.
#

# Set variables.
PROJECT_PATH="~/$SERVER_DIR"
SSH_AUTH="$SERVER_USER@$SERVER_HOST"

# Create file with privet ssh key from staging server.
cat >> ~/.ssh/stage_private_key <<EOF
$SERVER_PRIVATE_KEY
EOF
chmod 600 ~/.ssh/stage_private_key

# Upload the archive with artifact to the server.
echo scp -i ~/.ssh/stage_private_key $ARTIFACT_DIR/$BRANCH.tgz $SSH_AUTH:$PROJECT_PATH/$BRANCH.tgz
scp -i ~/.ssh/stage_private_key $ARTIFACT_DIR/$BRANCH.tgz $SSH_AUTH:$PROJECT_PATH/$BRANCH.tgz

# SSH to the server, unzip archive, rsync and deploy.
DEPLOY="cd $PROJECT_PATH &&
       mkdir tmp-$BRANCH
       tar -zxf $BRANCH.tgz -C tmp-$BRANCH &&
       rsync -a --del --exclude-from 'tmp-$BRANCH/deploy/.rsync-deploy-exclude' tmp-$BRANCH/ $SERVER_DIR-$BRANCH/ &&
       rm $BRANCH.tgz &&
       rm -rf tmp-$BRANCH &&
       cd $SERVER_DIR-$BRANCH &&
       make go_"$BRANCH"_deploy"

echo ssh -ttq -i ~/.ssh/stage_private_key $SSH_AUTH "export TERM='xterm' && $DEPLOY"
ssh -ttq -i ~/.ssh/stage_private_key $SSH_AUTH "export TERM='xterm' && $DEPLOY"