#!/bin/bash
#
# prestashop.inc.sh
#

function ps_download(){
    alert_info "Downloading of ${CMS} ${CMS_VERSION} ..."
    alert_info "$(alert_line)"

    if [[ -f "${PATH_WEB}composer.json" ]]; then
        alert_warning "${CMS} ${CMS_VERSION} was not downloaded, ${PATH_WEB} is not empty."
        alert_warning "It can be normal if you have already installed ${CMS} ${CMS_VERSION} previously."
    else
        cd "${PATH_WEB}" || return
        git clone -b "${CMS_VERSION}" --single-branch --depth 1 https://github.com/PrestaShop/PrestaShop.git .
        rm -rf .git .github
        alert_success "${CMS} ${CMS_VERSION} was downloaded with success..."
    fi

    alert_success "$(alert_line)"    
}

function ps_install_dependencies(){
    alert_info "Installation of ${CMS} ${CMS_VERSION} dependencies ..."
    alert_info "$(alert_line)"

    if [[ -f "${PATH_COMPOSER_JSON}" ]]; then
        cd "${PATH_WEB}" || return
        composer --global config process-timeout 0
        composer install --prefer-source --optimize-autoloader --no-suggest --no-progress
        alert_success "${CMS} ${CMS_VERSION} dependencies were installed with success..."
    else
        alert_warning "No dependencies to install, ${PATH_COMPOSER_JSON} was not found."
    fi

    alert_success "$(alert_line)"
}

function ps_install_db(){
    alert_info "Installation of ${CMS} ${CMS_VERSION} database ..."
    alert_info "$(alert_line)"

    if [[ -d "${PATH_WEB}install-dev" ]]; then
        cd "${PATH_WEB}install-dev" || return

        php index_cli.php \
            --domain="${DOMAIN}" \
            --language="${LANGUAGE}" \
            --timezone="${TIMEZONE}" \
            --db_name="${DB_NAME}" --db_user="${DB_USER}" --db_password="${DB_PASS}" \
            --email="${ADMIN_EMAIL}" --password="${ADMIN_PWD}" \
            --firstname="${ADMIN_FIRSTNAME}" --lastname="${ADMIN_LASTNAME}" \
            --ssl=1 \
            --newsletter=0 \
            --prefix="${PREFIX}"

        cd "${PATH_WEB}" || return
        random_str="$(tr -dc 'a-z0-9'  < /dev/urandom | fold -w 9 | head -n 1)";
        mv admin-dev "admin${random_str}"
        
        alert_success "${CMS} database was installed with success..."
    else
        alert_warning "${PATH_WEB}/install-dev does not exists database installation skipped"
    fi

    alert_info "$(alert_line)"
}

function ps_modules_install(){
    alert_info "We install usefull modules"
    alert_info "$(alert_line)"
    # add module to composer.json && download
    # launch installation of module
    if [ "${#ARR_MODULES[@]}" -ne 0 ]; then
        cd "${PATH_WEB}" || return
        for MODULE in "${ARR_MODULES[@]}"; do 
            composer require "prestashop/${MODULE}" --prefer-source --no-progress --no-suggest
            php bin/console prestashop:module install "${MODULE}"
        done
    fi

    alert_info "usefull modules installed"
    alert_info "$(alert_line)"
}

function ps_modules_disable(){
    alert_info "We disable useless modules"
    alert_info "$(alert_line)"

    # disable modules
    if [ "${#ARR_MODULES_DISABLE[@]}" -ne 0 ]; then
        cd "${PATH_WEB}" || return
        composer --global config process-timeout 0
        for MODULE in "${ARR_MODULES_DISABLE[@]}"; do 
            php bin/console prestashop:module disable "${MODULE}"
        done
    fi

    alert_info "Useless modules disabled"
    alert_info "$(alert_line)"
}