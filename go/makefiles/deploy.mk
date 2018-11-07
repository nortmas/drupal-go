## Run commands needed for DEV deployment.
go_dev_deploy:
	$(call DRUPAL_PHP_COMPOSER, install)
	$(call DRUPAL_PHP_ROBO, rebuild)
	make go_deploy_permissions

## Run commands needed for STAGE deployment.
go_stage_deploy:
	$(call DRUPAL_PHP_COMPOSER, install)
	$(call DRUPAL_PHP_ROBO, rebuild)
	make go_deploy_permissions

## Run commands needed for PROD deployment.
go_master_deploy:
	$(call DRUPAL_PHP_COMPOSER, install)
	$(call DRUPAL_PHP_ROBO, dbe)
	$(call DRUPAL_PHP_ROBO, rebuild)
	make go_deploy_permissions

## Set right permissions for the files and directories.
go_deploy_permissions:
	$(call DRUPAL_ROOT_PHP, chmod 755 /var/www/html/drush/drush-run.sh)
	make go_set_files_permissions