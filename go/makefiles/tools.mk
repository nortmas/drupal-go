## Get into the php container.
go_shell:
	docker-compose exec php sh

## Run behat test.
go_run_behat:
	$(call DRUPAL_PHP, /bin/bash -c "./vendor/bin/behat -f pretty --out=std -f junit --out=tests/behat/_output -f html -c tests/behat/behat.yml -p default")

## Check codebase with phpcs sniffers to make sure it conforms https://www.drupal.org/docs/develop/standards
go_code_sniff:
	docker run --rm \
		-v $(shell pwd)/web/profiles/$(PROFILE_NAME):/work/profile \
		-v $(shell pwd)/web/modules/custom:/work/modules \
		-v $(shell pwd)/web/themes/$(THEME_NAME):/work/themes \
		skilldlabs/docker-phpcs-drupal phpcs -s --colors \
		--standard=Drupal,DrupalPractice \
		--extensions=php,module,inc,install,profile,theme,yml \
		--ignore=*.css,*.md,*.js .
	docker run --rm \
		-v $(shell pwd)/web/profiles/$(PROFILE_NAME):/work/profile \
		-v $(shell pwd)/web/modules/custom:/work/modules \
		-v $(shell pwd)/web/themes/$(THEME_NAME):/work/themes \
		skilldlabs/docker-phpcs-drupal phpcs -s --colors \
		--standard=Drupal,DrupalPractice \
		--extensions=js \
		--ignore=*.css,*.md,libraries/*,styleguide/* .

## Fix codebase according to Drupal standards https://www.drupal.org/docs/develop/standards
go_code_fix:
	docker run --rm \
		-v $(shell pwd)/web/profiles/$(PROFILE_NAME):/work/profile \
		-v $(shell pwd)/web/modules/custom:/work/modules \
		-v $(shell pwd)/web/themes/$(THEME_NAME):/work/themes \
		skilldlabs/docker-phpcs-drupal phpcbf -s --colors \
		--standard=Drupal,DrupalPractice \
		--extensions=php,module,inc,install,profile,theme,yml,txt,md \
		--ignore=*.css,*.md,*.js .
	docker run --rm \
		-v $(shell pwd)/web/profiles/$(PROFILE_NAME):/work/profile \
		-v $(shell pwd)/web/modules/custom:/work/modules \
		-v $(shell pwd)/web/themes/$(THEME_NAME):/work/themes \
		skilldlabs/docker-phpcs-drupal phpcbf -s --colors \
		--standard=Drupal,DrupalPractice \
		--extensions=js \
		--ignore=*.css,*.md,libraries/*,styleguide/* .