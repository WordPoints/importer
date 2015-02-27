#!/bin/bash

set -e
shopt -s expand_aliases

# Install CubePoints when running tests.
install-cubepoints() {

	mkdir -p /tmp/cubepoints
	curl -s https://downloads.wordpress.org/plugin/cubepoints.3.2.1.zip > /tmp/cubepoints.zip
	unzip /tmp/cubepoints.zip -d /tmp/

	mkdir -p /tmp/wordpress/wp-content/plugins/cubepoints
	ln -s /tmp/cubepoints/ /tmp/wordpress/wp-content/plugins/cubepoints
}

alias setup-phpunit="\setup-phpunit; install-cubepoints"

set +e

# EOF
