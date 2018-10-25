CURRENT_PATH=$(shell pwd)
INIT_PHP_NAME=go_php
INIT_PHP_IMAGE=wodby/drupal-php:7.1-dev-4.8.5
INIT_PHP_CONTAINER=$(shell docker ps --filter name=$(INIT_PHP_NAME) --format "{{ .ID }}")
INIT_PHP_COMPOSER=docker exec -ti -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) $(INIT_PHP_CONTAINER) composer ${1}
INIT_PHP_ROBO=docker exec -ti -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) $(INIT_PHP_CONTAINER) vendor/bin/robo ${1}
DRUPAL_PHP_ROBO=docker-compose exec -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php vendor/bin/robo ${1}
DRUPAL_PHP_COMPOSER=docker-compose exec -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php composer ${1}
DRUPAL_PHP_DRUSH=docker-compose exec -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php drush ${1}
DRUPAL_PHP=docker-compose exec -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php ${1}

include $(CURRENT_PATH)/go/makefiles/help.mk
include $(CURRENT_PATH)/go/makefiles/tools.mk

.PHONY: go go_set_php_container go_run_in_php go_php_kill go_mac go_up go_down go_reset_structure go_run_behat

## Roll out the environment.
go:
	make go_set_php_container
	make go_run_in_php
	make go_php_kill
	make go_up
	sleep 10

## Install Drupal.
go_drupal_install:
	$(call DRUPAL_PHP_ROBO, go)

## Run php container in order to prepare project.
go_set_php_container:
	docker run -d --rm --name $(INIT_PHP_NAME) -v $(CURRENT_PATH):/var/www/html $(INIT_PHP_IMAGE)

## Run commands in php container.
go_run_in_php:
	$(call INIT_PHP_COMPOSER, install)
	$(call INIT_PHP_ROBO, prepare)

## Kill php container.
go_php_kill:
	docker rm -f $(INIT_PHP_CONTAINER)
	docker rmi -f $(INIT_PHP_IMAGE)

## Create .env file with specific settings for Mac.
go_mac:
	echo 'OS=macos-' > .env

## Up the docker containers.
go_up:
	@echo "Build and run containers..."
	docker-compose up -d --remove-orphans

## Stop and remove the docker containers and networks.
go_down:
	@echo "Removing network & containers"
	docker-compose down --remove-orphans

## Restart containers.
go_restart:
	make go_down
	make go_up

## Reset file/directory structure to the initial Drupal Go state.
go_reset_structure:
	$(call DRUPAL_PHP_ROBO, reset_file_structure)
	docker-compose down -v --rmi all
	rm -rf vendor
	rm -rf web
	rm -f RoboFile.php
	rm -f docker-compose.yml

## Run behat test.
go_run_behat:
	$(call DRUPAL_PHP, /bin/bash -c "./vendor/bin/behat -f pretty --out=std -f junit --out=tests/behat/_output -f html -c tests/behat/behat.yml -p default")
