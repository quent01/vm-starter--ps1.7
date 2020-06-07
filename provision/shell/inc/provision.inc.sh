#!/bin/bash
#
# provisionning.inc.sh
#

function start_provisionning(){
    alert_info "Provisioning virtual machine..."
    alert_info "$(alert_line)"
    alert_info "You choose to install ${CMS} version 1.7 with the stack ${STACK}"
    alert_info "Your project directory will be ${PATH_PUBLIC} and web root ${PATH_WEB}"
    alert_info "It will work on php ${PHP_BASE_VERSION}"
}


function prestashop_provisionning(){
    alert_info "$(alert_line)"
    alert_info "Provisioning Prestashop..."
    alert_info "$(alert_line)"

    mkdir -p "${PATH_WEB}"

    ps_download
    ps_install_dependencies
    ps_install_db
    ps_modules_install
    ps_modules_disable

    alert_success "$(alert_line)"
    alert_success "End Provisioning Prestashop..."
    alert_success "$(alert_line)"
}