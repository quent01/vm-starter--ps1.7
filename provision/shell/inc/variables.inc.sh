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
PROJECT_DIR="public"
WEB_ROOT="/var/www/public/web/"
LOCALE="fr_FR"
TIMEZONE="Europe/Paris"
# ADMIN_USER="tiz"
# ADMIN_PWD="azertiz67"
# ADMIN_EMAIL="tech@tiz.fr"
# ADMIN_FIRSTNAME="Agence"
# ADMIN_LASTNAME="Tiz"

# Modules to install
# @desc : This is the part you can edit
# ---------------------------------------
ARR_MODULES[0]=""


# Modules To uninstall
# @desc : This is the part you can edit
# ---------------------------------------
ARR_MODULE_DISABLE[0]=""



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
PATH_COMPOSER_JSON="${WEB_ROOT}/composer.json"
PATH_PROVISION="/var/www/provision/"
PATH_PROVISION_SHELL="${PATH_PROVISION}shell/"
PATH_VAGRANT="/home/vagrant/"





# Themes
# ---------------------------------------
