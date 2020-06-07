#!/bin/bash
#
# variables.inc.sh
#

# Configuration
# @desc : This is the part you can edit
# ---------------------------------------
# Site_name must contain no space
SITE_NAME="PS"
CMS_VERSION="1.7.6.x"
DOMAIN="presta.test"
PREFIX="ps_"
LANGUAGE="fr"
LOCALE="fr_FR"
TIMEZONE="Europe/Paris"
ADMIN_EMAIL="admin@gmail.com"
ADMIN_PWD="admin"
ADMIN_FIRSTNAME="admin"
ADMIN_LASTNAME="admin"

# Modules to install
# @desc : This is the part you can edit
# ---------------------------------------
ARR_MODULES[0]=""


# Modules To uninstall
# @desc : This is the part you can edit
# ---------------------------------------
ARR_MODULES_DISABLE[0]=""



# Vagrant variables
# ---------------------------------------
DB_NAME="scotchbox"
PHP_BASE_VERSION="7.2"
DB_USER="root"
DB_PASS="root"
STACK="apache"
CMS="prestashop"


# Filenames
# ---------------------------------------


# Paths
# ---------------------------------------
PATH_PUBLIC="/var/www/public/"
PATH_WEB="${PATH_PUBLIC}web/"
PATH_COMPOSER_JSON="${PATH_WEB}/composer.json"
PATH_PROVISION="/var/www/provision/"
PATH_PROVISION_SHELL="${PATH_PROVISION}shell/"
PATH_VAGRANT="/home/vagrant/"





# Themes
# ---------------------------------------
