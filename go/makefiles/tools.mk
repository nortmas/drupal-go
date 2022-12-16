## Get into the php container.
go_sh:
	docker compose exec php sh

## Run behat tests.
go_run_behat:
	$(call DRUPAL_PHP, /bin/bash -c "./vendor/bin/behat -f pretty --out=std -f junit --out=tests/behat/_output -f html -c tests/behat/behat.yml -p default")

## Update Drupal core with dependencies.
go_drupal_update:
	$(call DRUPAL_PHP_COMPOSER, require drupal/core-recommended drupal/core-composer-scaffold drupal/core-project-message --update-with-all-dependencies)
	$(call DRUPAL_PHP_DRUSH, updb)
	$(call DRUPAL_PHP_DRUSH, cr)

## Update Drupal translations.
go_update_translations:
	$(call DRUPAL_PHP_DRUSH, locale-check)
	$(call DRUPAL_PHP_DRUSH, locale-update)

## Encrypt env.extra file with the password.
go_env_enc:
	openssl enc -aes-256-cbc -salt -pbkdf2 -in .env.extra -out .env.extra.enc

## Decrypt the env.extra file using the password.
go_env_dec:
	openssl aes-256-cbc -d -salt -pbkdf2 -in .env.extra.enc -out .env.extra

## Check codebase with phpcs sniffers to make sure it conforms https://www.drupal.org/docs/develop/standards
go_code_sniff:
	docker run --rm \
		-v $(shell pwd)/web/profiles:/work/profile \
		-v $(shell pwd)/web/modules/custom:/work/modules \
		-v $(shell pwd)/web/themes/custom:/work/themes \
		skilldlabs/docker-phpcs-drupal phpcs -s --colors \
		--standard=Drupal,DrupalPractice \
		--extensions=php,module,inc,install,profile,theme,yml \
		--ignore=*.css,*.md,*.js .
	docker run --rm \
		-v $(shell pwd)/web/profiles:/work/profile \
		-v $(shell pwd)/web/modules/custom:/work/modules \
		-v $(shell pwd)/web/themes/custom:/work/themes \
		skilldlabs/docker-phpcs-drupal phpcs -s --colors \
		--standard=Drupal,DrupalPractice \
		--extensions=js \
		--ignore=*.css,*.md,libraries/*,styleguide/* .

## Fix codebase according to Drupal standards https://www.drupal.org/docs/develop/standards
go_code_fix:
	docker run --rm \
		-v $(shell pwd)/web/profiles/:/work/profile \
		-v $(shell pwd)/web/modules/custom:/work/modules \
		-v $(shell pwd)/web/themes/custom:/work/themes \
		skilldlabs/docker-phpcs-drupal phpcbf -s --colors \
		--standard=Drupal,DrupalPractice \
		--extensions=php,module,inc,install,profile,theme,yml,txt,md \
		--ignore=*.css,*.md,*.js .
	docker run --rm \
		-v $(shell pwd)/web/profiles/:/work/profile \
		-v $(shell pwd)/web/modules/custom:/work/modules \
		-v $(shell pwd)/web/themes/custom:/work/themes \
		skilldlabs/docker-phpcs-drupal phpcbf -s --colors \
		--standard=Drupal,DrupalPractice \
		--extensions=js \
		--ignore=*.css,*.md,libraries/*,styleguide/* .