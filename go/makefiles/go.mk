## Include .env file if exists.
ifneq ("$(wildcard .env)","")
    include .env
endif

## Set variables.
CURRENT_PATH=$(shell pwd)
INIT_PHP_NAME=go_php
INIT_PHP_IMAGE=wodby/drupal-php:7.1-dev-$(OS)4.8.5
INIT_PHP_CONTAINER=$(shell docker ps --filter name=$(INIT_PHP_NAME) --format "{{ .ID }}")
INIT_PHP_COMPOSER=docker exec -ti -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) $(INIT_PHP_CONTAINER) composer ${1}
INIT_PHP_ROBO=docker exec -ti -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) $(INIT_PHP_CONTAINER) vendor/bin/robo ${1}
DRUPAL_PHP_ROBO=docker-compose exec -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php vendor/bin/robo ${1}
DRUPAL_PHP_COMPOSER=docker-compose exec -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php composer ${1}
DRUPAL_PHP_DRUSH=docker-compose exec -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php drush ${1}
DRUPAL_ROOT_PHP=docker-compose exec --user root:root -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php ${1}
DRUPAL_PHP=docker-compose exec -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) php ${1}

## Include addtional commands.
include $(CURRENT_PATH)/go/makefiles/help.mk
include $(CURRENT_PATH)/go/makefiles/tools.mk
include $(CURRENT_PATH)/go/makefiles/deploy.mk

.PHONY: go_prepare_env go_set_php_container go_run_in_php go_mac go_php_kill go_up go_down go_reset_structure

## Roll out the environment.
go_prepare_env:
	make go_set_php_container
	make go_run_in_php
	make go_php_kill
	make go_up
	sleep 15

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

## Create .env file with specific settings for Mac.
go_mac:
	echo 'OS=macos-\nPHP_XDEBUG_REMOTE_CONNECT_BACK=0' > .env

## Create .env file with specific settings for Linux.
go_lin:
	echo 'OS=\nPHP_XDEBUG_REMOTE_CONNECT_BACK=1' > .env

## Kill php container.
go_php_kill:
	docker rm -f $(INIT_PHP_CONTAINER)
	docker rmi -f $(INIT_PHP_IMAGE)

## Reset file/directory structure to the initial Drupal Go state.
go_reset_structure:
	$(call DRUPAL_PHP_ROBO, reset_file_structure)
	docker-compose down -v --rmi all
	rm -rf vendor
	rm -rf web
	rm -f docker-compose.yml

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