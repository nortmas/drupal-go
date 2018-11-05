## Run commands needed for DEV deployment.
go_dev_deploy:
	$(call DRUPAL_PHP_COMPOSER, install)
	$(call DRUPAL_PHP_ROBO, rebuild)
	make go_set_file_permission

## Run commands needed for STAGE deployment.
go_stage_deploy:
	$(call DRUPAL_PHP_COMPOSER, install)
	$(call DRUPAL_PHP_ROBO, rebuild)
	make go_set_file_permission

## Run commands needed for PROD deployment.
go_master_deploy:
	$(call DRUPAL_PHP_COMPOSER, install)
	$(call DRUPAL_PHP_ROBO, dbe)
	$(call DRUPAL_PHP_ROBO, rebuild)
	make go_set_file_permission

## Set right permissions for the files and derictories.
go_set_file_permission:
	$(call DRUPAL_PHP, chmod 755 /var/www/html/drush/drush-run.sh)
	$(call DRUPAL_PHP, chmod -R 775 /var/www/html/web/sites/default/files)
	$(call DRUPAL_PHP, chown -R wodby:www-data /var/www/html/web/sites/default/files)