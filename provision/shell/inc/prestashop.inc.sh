#!/bin/bash
#
# prestashop.inc.sh
#

function prestashop_install_dependencies(){
    alert_info "Installation of Prestashop dependencies ..."
    alert_info "$(alert_line)"    
    composer --global config process-timeout 0
    composer install --prefer-dist --optimize-autoloader
    alert_success "Prestashop dependencies were installed with success..."
    alert_success "$(alert_line)"
}