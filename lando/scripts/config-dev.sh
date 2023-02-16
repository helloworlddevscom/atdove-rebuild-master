#!/usr/bin/env bash

# This file is configured in .lando.yml. Run: lando drupal-config-dev.
# It will enable development modules using config_split module.

NORMAL="\033[0m"
RED="\033[31m"
YELLOW="\033[32m"
ORANGE="\033[33m"
PINK="\033[35m"
BLUE="\033[34m"

echo
echo -e "${ORANGE}Do you want to enable development modules on your local Lando environment (devel, devel_php, kint, webprofiler) using config_split module? (y/n):${BLUE}"
echo
read REPLY
if [[ ! "$REPLY" =~ ^[Yy]$ ]]
then
  exit 1
fi

echo
echo -e "${YELLOW}Enabling development modules...${NORMAL}"
echo
sed -i "s/config_split.config_dev_devel']\['status'] = FALSE/config_split.config_dev_devel']\['status'] = TRUE/" /app/web/sites/default/settings.local.php
sed -i "s/config_split.config_dev_syslog']\['status'] = FALSE/config_split.config_dev_syslog']\['status'] = TRUE/" /app/web/sites/default/settings.local.php
drush cr
drush config-split:import config_dev_devel -y
drush config-split:import config_dev_syslog -y
echo
