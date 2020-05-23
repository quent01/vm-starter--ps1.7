#!/bin/bash
#
# prestashop.inc.sh
#

function prestashop_download(){
    alert_info "Downloading of ${CMS} ${CMS_VERSION} ..."
    alert_info "$(alert_line)"

    if [ "$(ls -A "${WEB_ROOT}")" ]; then
        alert_warning "${CMS} ${CMS_VERSION} was not downloaded, ${WEB_ROOT} is not empty."
        alert_warning "It can be normal if you have already installed ${CMS} ${CMS_VERSION} previously."
    else
        cd "${WEB_ROOT}" || return
        git clone -b "${CMS_VERSION}" --single-branch --depth 1 https://github.com/PrestaShop/PrestaShop.git .
        rm -rf .git .github
        alert_success "${CMS} ${CMS_VERSION} was downloaded with success..."
    fi

    alert_success "$(alert_line)"    
}

function prestashop_install_dependencies(){
    alert_info "Installation of ${CMS} ${CMS_VERSION} dependencies ..."
    alert_info "$(alert_line)"

    if [[ -f "${PATH_COMPOSER_JSON}" ]]; then
        cd "${WEB_ROOT}" || return
        composer --global config process-timeout 0
        composer install --prefer-source --optimize-autoloader --no-suggest --no-progress
        alert_success "${CMS} ${CMS_VERSION} dependencies were installed with success..."
    else
        alert_warning "No dependencies to install, ${PATH_COMPOSER_JSON} was not found."
    fi

    alert_success "$(alert_line)"
}