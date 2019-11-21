#!/bin/bash
#
# alerts.sh
#

# Alerts
# $1 : --warning, --info, --success, --error
# $2 : message
function alert() {
    level=$1
    msg=$2
    if [ $level == '--warning']; then 
        alert_warning $2
    elif [ $level == '--info' ]; then
        alert_info $2
    elif [ $level == '--success' ]; then
        alert_success $2
    elif [ $level == '--error' ]; then
        alert_error $2
    else
        alert_info $2
    fi
}

function alert_warning() {
    MSG=$1
    echo -e "${C_YELLOW} ${MSG} ${C_NC}"
}

function alert_error() {
    MSG=$1
    echo -e "${C_RED} ${MSG} ${C_NC}"
}

function alert_info() {
    MSG=$1
    echo -e "${C_BLUE} ${MSG} ${C_NC}"
}

function alert_success() {
    MSG=$1
    echo -e "${C_GREEN} ${MSG} ${C_NC}"
}

function alert_line(){
    echo "==============================="
}