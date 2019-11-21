#!/bin/bash
#
# provisionning.inc.sh
#

function start_provisionning(){
    alert_info "Provisioning virtual machine..."
    alert_info "$(alert_line)"
    alert_info "You choose to install ${CMS} version 1.7 with the stack ${STACK}"
    alert_info "Your project directory will be ${PROJECT_DIR} and web root ${WEB_ROOT}"
    alert_info "It will work on php ${PHP_BASE_VERSION}"
}

function apache_provisionning(){
    alert_info "$(alert_line)"
    alert_info "Provisioning Apache..."
    alert_info "$(alert_line)"
    
    apache_configure_vhost

    alert_success "$(alert_line)"
    alert_success "End Provisioning Apache..."
    alert_success "$(alert_line)"
}


function presatshop_provisionning(){
    alert_info "$(alert_line)"
    alert_info "Provisioning Prestashop..."
    alert_info "$(alert_line)"

    cd /var/www/
    mkdir -p ${PROJECT_DIR}
    cd "${PROJECT_DIR}"

    prestashop_install_dependencies

    alert_success "$(alert_line)"
    alert_success "End Provisioning Prestashop..."
    alert_success "$(alert_line)"
}