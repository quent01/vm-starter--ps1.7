#!/bin/bash
#
# apache.inc.sh
#

function apache_configure_vhost(){
    # we modify conf file with settings
     sed -i.bak "s|%WEB_ROOT%|${WEB_ROOT}|g" "${PATH_PROVISION_APACHE}${FILE_APACHE_CONF}"
    
    # we copy conf file to vm
    cp "${PATH_PROVISION_APACHE}${FILE_APACHE_CONF}" "${PATH_A2_SITES_AVAILABLE}"
    
    # we reset initial file
    cd "${PATH_PROVISION_APACHE}" || return
    rm "${FILE_APACHE_CONF}"
    mv "${FILE_APACHE_CONF}.bak" "${FILE_APACHE_CONF}"

    sudo chmod 644 "${PATH_A2_SITES_AVAILABLE}${FILE_APACHE_CONF}"
    sudo a2dissite 000-default
    sudo a2ensite "${FILE_APACHE}"
}