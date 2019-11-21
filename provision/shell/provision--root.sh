#!/bin/bash
#
# provision--root.inc.sh
#

source "/var/www/provision/shell/inc/variables.inc.sh"
source "${PATH_PROVISION_SHELL}/functions.sh"

# By storing the date now, we can calculate the duration of provisioning at the
# end of this script.
start_seconds="$(date +%s)"

# Network Detection
#
# Make an HTTP request to google.com to determine if outside access is available
# to us. If 3 attempts with a timeout of 5 seconds are not successful, then we'll
# skip a few things further in provisioning rather than create a bunch of errors.
if [[ "$(wget --tries=3 --timeout=5 --spider http://google.com 2>&1 | grep 'connected')" ]]; then
	alert_info "Network connection detected..."
	ping_result="Connected"
else
	alert_info "Network connection not detected. Unable to reach google.com..."
	ping_result="Not Connected"
fi

start_provisionning
presatshop_provisionning

end_seconds="$(date +%s)"
provisionning_time="$(expr $end_seconds - $start_seconds)"
alert_info "Provisioning complete in ${provisionning_time} seconds"
if [[ $ping_result == "Connected" ]]; then
	alert_info "External network connection established, packages up to date."
else
	alert_info "No external network available. Package installation and maintenance skipped."
fi