## Run commands needed for DEV deployment.
go_dev_deploy:
	$(call DRUPAL_PHP_COMPOSER, install --no-interaction)
	$(call DRUPAL_PHP_ROBO, set_correct_permissions)
	$(call DRUPAL_PHP_ROBO, rebuild)

## Run commands needed for STAGE deployment.
go_stage_deploy:
	$(call DRUPAL_PHP_COMPOSER, install --no-interaction)
	$(call DRUPAL_PHP_ROBO, set_correct_permissions)
	$(call DRUPAL_PHP_ROBO, rebuild)

## Run commands needed for PROD deployment.
go_prod_deploy:
	$(call DRUPAL_PHP_COMPOSER, install --no-interaction)
	$(call DRUPAL_PHP_ROBO, dbe)
	$(call DRUPAL_PHP_ROBO, set_correct_permissions)
	$(call DRUPAL_PHP_ROBO, rebuild)