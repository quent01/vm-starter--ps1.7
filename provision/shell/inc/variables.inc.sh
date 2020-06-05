#!/bin/bash
#
# variables.inc.sh
#

# Drupal configuration
# @desc : This is the part you can edit
# ---------------------------------------
# Site_name must contain no space
SITE_NAME="PS"
PROJECT_DIR="public"
WEB_ROOT="/var/www/public/web/"
LOCALE="fr_FR"
TIMEZONE="Europe/Paris"
# ADMIN_USER="tiz"
# ADMIN_PWD="azertiz67"
# ADMIN_EMAIL="tech@tiz.fr"
# ADMIN_FIRSTNAME="Agence"
# ADMIN_LASTNAME="Tiz"


# Vagrant variables
# ---------------------------------------
DB_NAME="scotchbox"
DB_USER="root"
DB_PASS="root"
PHP_BASE_VERSION="7.2"
STACK="apache"
CMS="prestashop"
CMS_VERSION="1.7.6.x"


# Filenames
# ---------------------------------------


# Paths
# ---------------------------------------
PATH_A2_SITES_AVAILABLE="/etc/apache2/sites-available/"
PATH_PUBLIC="/var/www/public/"
PATH_COMPOSER_JSON="${WEB_ROOT}/composer.json"
PATH_PROVISION="/var/www/provision/"
PATH_PROVISION_APACHE="${PATH_PROVISION}apache/"
PATH_PROVISION_SHELL="${PATH_PROVISION}shell/"
PATH_VAGRANT="/home/vagrant/"
PATH_DRUSH_ALIASES="${PATH_VAGRANT}/.drush/${FILE_DRUSH_ALIASES}"
PATH_MODULES_CONTRIB="${WEB_ROOT}modules/contrib/"


# Modules
# ---------------------------------------
ARR_MODULES[0]=""

# Modules To disable
# ---------------------------------------
# ARR_MODULE_DISABLE[0]="test"

# Themes
# ---------------------------------------
