MAKEFLAGS += --no-print-directory

## Include .env file if exists.
ifneq ("$(wildcard .env)","")
    include .env
endif

## Set variables.
CURRENT_PATH=$(shell pwd)
INIT_PHP_NAME=go_php
INIT_PHP_IMAGE=wodby/drupal-php:8.1-dev-${OS}4.38.2
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

.SILENT: go_env go_lin go_mac go_check_env

## Roll out the environment.
go_prepare_env:
	make go_set_php_container
	make go_run_in_php
	make go_php_kill
	make go_up
	sleep 15

## Check if the .env exists.
go_check_env:
	[ -f acme.json ] && true || (touch acme.json && chmod 600 acme.json)
	[ -f .env ] && true || (echo "\033[31m File .env doesn't exist. Please run go_lin or go_mac commands. \033[0m" && exit 1)

## Install Drupal.
go_drupal_install:
	$(call DRUPAL_PHP_ROBO, go)

## Run php container in order to prepare project.
go_set_php_container:
	make go_check_env
	docker run -d --rm --name $(INIT_PHP_NAME) -v $(CURRENT_PATH):/var/www/html $(INIT_PHP_IMAGE)

## Run commands in php container.
go_run_in_php:
	$(call INIT_PHP_COMPOSER, install)
	$(call INIT_PHP_ROBO, prepare)

## Add specific settings for Mac to the .env file.
go_mac:
	make go_env
	sed -i '' -e "2s/^//p; 2s/^.*/OS=macos-/" .env
	sed -i '' -e "12s/^//p; 12s/^.*/PHP_XDEBUG_REMOTE_HOST=host.docker.internal/" .env
	if [ -f .env.extra.enc ]; then make go_add_extra; fi
	#if [ -f .env.extra ]; then cat .env.extra >> .env; fi

## Add specific settings for Linux to the .env file.
go_lin:
	make go_env
	sed -i '2 i\OS=' .env
	if [ -f .env.extra.enc ]; then make go_add_extra; fi

## Add encrypeted info form the file .env.extra.enc to the .env file.
go_add_extra:
	openssl aes-256-cbc -d -salt -pbkdf2 -in .env.extra.enc -p | awk 'NR>3' >> .env

## Create .env file using template.
go_env:
	if [ -f .env ]; then rm -f .env; fi
	cp -n go/templates/.env .env

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
	make go_check_env
	docker-compose up -d --remove-orphans

## Stop and remove the docker containers and networks.
go_down:
	docker-compose down --remove-orphans

## Stop and remove all docker containers, images and networks.
go_down_rm:
	docker-compose down --rmi all

## Restart containers.
go_restart:
	make go_down
	make go_up