#!/usr/bin/env bash

# This file is configured in .lando.yml. Run: lando drupal-config-dev-disable.
# It will disable development modules using config_split module.

NORMAL="\033[0m"
RED="\033[31m"
YELLOW="\033[32m"
ORANGE="\033[33m"
PINK="\033[35m"
BLUE="\033[34m"

echo
echo -e "${RED}WARNING: This will delete all currently unexported config changes because it requires running drush cim -y;"
echo
echo -e "${ORANGE}Do you want to disable development modules on your local Lando environment (devel, devel_php, kint, webprofiler) using config_split module? (y/n):${BLUE}"
echo
read REPLY
if [[ ! "$REPLY" =~ ^[Yy]$ ]]
then
  exit 1
fi

echo
echo -e "${YELLOW}Disabling development modules...${NORMAL}"
echo
/app/lando/scripts/config-dev-disable-auto.sh
drush cr
drush cim -y
echo
