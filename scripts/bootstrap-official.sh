#!/usr/bin/env sh
set -eu

# Mirrors the CakePHP 4 official startup guide:
# composer create-project --prefer-dist cakephp/app:4.* <dir>
#
# This script intentionally generates into a separate directory so existing
# refactoring examples are not overwritten.

TARGET="${1:-official-cakephp-app}"

composer create-project --prefer-dist 'cakephp/app:4.*' "$TARGET"
cd "$TARGET"
composer require 'cakephp/cakephp:4.5.2' --with-all-dependencies
