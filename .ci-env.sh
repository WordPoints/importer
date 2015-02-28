#!/bin/bash

set -e
shopt -s expand_aliases

# Install CubePoints when running tests.
install-cubepoints() {

	mkdir -p /tmp/wordpress/wp-content/plugins/cubepoints
	curl -s https://downloads.wordpress.org/plugin/cubepoints.3.2.1.zip > /tmp/cubepoints.zip
	unzip /tmp/cubepoints.zip -d /tmp/wordpress/wp-content/plugins/
}

alias setup-phpunit="\setup-phpunit; install-cubepoints"

custom-setup-composer() {

	# We always need to do this when collecting code coverage, even if there are no
	# composer dependencies.
	if [[ $DO_CODE_COVERAGE == 1 && $TRAVISCI_RUN == phpunit ]]; then
		composer require satooshi/php-coveralls:dev-master
		mkdir -p build/logs
		return;
	fi

	# No dependencies, no need to continue.
	if [ ! -e composer.json ]; then
		return
	fi

	# Composer requires PHP 5.3.
	if [[ $TRAVIS_PHP_VERSION == '5.2' ]]; then
		phpenv global 5.3
		composer install
		phpenv global "$TRAVIS_PHP_VERSION"
	else
		composer install
	fi
}

alias setup-composer="custom-setup-composer"

set +e

# EOF
