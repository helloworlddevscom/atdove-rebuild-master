#!/usr/bin/env bash

# This file is configured in .lando.yml. Run: lando logs-drupal.
# It will enable the syslog module using config_split module,
# start the rsyslog service and tail the log file. This is really
# only necessary because Drush 10 removed the ability to tail `drush wd-show`.
# In order to work, syslog must be configured at /admin/config/development/logging
# like this: Syslog facilty: LOG_LOCAL7 Syslog format: !timestamp|!type|!request_uri|!referer|!uid|!link|!message

NORMAL="\033[0m"
RED="\033[31m"
YELLOW="\033[32m"
ORANGE="\033[33m"
PINK="\033[35m"
BLUE="\033[34m"

echo
echo -e "${YELLOW}Enabling syslog module...${NORMAL}"
echo
sed -i "s/config_split.config_dev_syslog']\['status'] = FALSE/config_split.config_dev_syslog']\['status'] = TRUE/" /app/web/sites/default/settings.local.php
drush cr
drush config-split:import config_dev_syslog -y
service rsyslog start;
echo
echo -e "${YELLOW}Tailing logs...${NORMAL}"
echo
tail -f /app/lando/logs/drupal.log | tr -s '|' \\t
