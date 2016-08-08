#!/bin/bash

set -e
shopt -s expand_aliases

# Install CubePoints when running tests.
install-cubepoints() {

	mkdir -p /tmp/wordpress/src/wp-content/plugins/cubepoints
	curl -s https://downloads.wordpress.org/plugin/cubepoints.3.2.1.zip > /tmp/cubepoints.zip
	unzip /tmp/cubepoints.zip -d /tmp/wordpress/src/wp-content/plugins/
}

# Sets up custom configuration.
function wordpoints-dev-lib-config() {

	alias setup-phpunit="\setup-phpunit; install-cubepoints"
}

set +e

# EOF
