#!/bin/bash
#
# variables.inc.sh
#

# Drupal configuration
# @desc : This is the part you can edit
# ---------------------------------------
# Site_name must contain no space
SITE_NAME="VAL"
PROJECT_DIR="public"
WEB_ROOT="/var/www/public/"
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
PHP_BASE_VERSION="7"
STACK="apache"
CMS="prestashop"


# Colors
# ---------------------------------------
C_RED='\033[0;31m'
C_YELLOW='\033[1;33m'
C_NC='\033[0m'              # No Color
C_GREEN='\033[0;32m'
C_BRN='\033[0;33m'
C_BLUE='\033[0;34m'
C_MAGENTA='\033[0;35m'
C_CYAN='\033[0;36m'
C_WHITE='\033[0;97m'


# Filenames
# ---------------------------------------
# FILE_APACHE="001-drupal"
# FILE_APACHE_CONF="${FILE_APACHE}.conf"
# FILE_DRUSH_ALIASES="aliases.drushrc.php"


# Paths
# ---------------------------------------
PATH_A2_SITES_AVAILABLE="/etc/apache2/sites-available/"
PATH_PUBLIC="/var/www/public/"
PATH_COMPOSER_JSON="${PATH_PUBLIC}/composer.json"
PATH_PROVISION="/var/www/provision/"
PATH_PROVISION_APACHE="${PATH_PROVISION}apache/"
PATH_PROVISION_SHELL="${PATH_PROVISION}shell/"
PATH_VAGRANT="/home/vagrant/"
PATH_DRUSH_ALIASES="${PATH_VAGRANT}/.drush/${FILE_DRUSH_ALIASES}"
PATH_MODULES_CONTRIB="${WEB_ROOT}modules/contrib/"


# Modules
# ---------------------------------------
ARR_MODULES[0]="module_filter"
ARR_MODULES[1]="eu_cookie_compliance"
ARR_MODULES[2]="better_exposed_filters"
ARR_MODULES[3]="paragraphs"
ARR_MODULES[4]="pathauto"
ARR_MODULES[5]="google_analytics"
ARR_MODULES[6]="admin_toolbar"
ARR_MODULES[7]="field_group"
ARR_MODULES[8]="twig_tweak"
ARR_MODULES[9]="config_split"
ARR_MODULES[10]="metatag"
ARR_MODULES[11]="admin_toolbar"
ARR_MODULES[12]="block_class"

# Modules DEV
# ---------------------------------------
ARR_MODULES_DEV[0]="devel"
ARR_MODULES_DEV[1]="delete_all"

# Modules To disable
# ---------------------------------------
# ARR_MODULE_DISABLE[0]="test"

# Themes
# ---------------------------------------
