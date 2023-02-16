#!/usr/bin/env bash

# This file is configured in .lando.yml. Run: lando env-import.
# It will run database updates, imports config and clear caches
# on a remote Pantheon env. This is useful if importing config did not succeed
# for whatever reason during the deploy process.

NORMAL="\033[0m"
RED="\033[31m"
YELLOW="\033[32m"
ORANGE="\033[33m"
PINK="\033[35m"
BLUE="\033[34m"

echo
echo -e "${RED}WARNING: This will affect a remote Pantheon environment."
echo
echo -e "${ORANGE}Are you sure you want to run database updates, import config and clear caches on a remote Pantheon environment? (y/n):${BLUE}"
echo
read REPLY
if [[ ! "$REPLY" =~ ^[Yy]$ ]]
then
  exit 1
fi

echo
echo -e "${ORANGE}Enter the environment to run database updates, import config and clear caches on (dev/test/live)."
echo -e "You can also enter a multidev environment (e.g. pr-24):${BLUE}"
echo
read REPLY_ENV
if [ "$REPLY_ENV" = "live" ]
then
  echo
  echo -e "${ORANGE}Are you sure you want to run database updates, import config and clear caches on prod? This will wipe out any config changes that are currently on prod. (y/n):${BLUE}"
  echo
  read REPLY_CONFIRM
  if [[ ! "$REPLY_CONFIRM" =~ ^[Yy]$ ]]
  then
    exit 1
  fi
fi

#echo
#echo -e "${YELLOW}Enabling maintenance mode...${NORMAL}"
#echo
#terminus remote:drush atdove."$REPLY_ENV" state:set system.maintenance_mode 1
echo
echo -e "${YELLOW}Running database updates, config import and clearing caches on $REPLY_ENV environment...${NORMAL}"
echo
terminus remote:drush atdove."$REPLY_ENV" -- updb -y
echo
echo -e "${YELLOW}Running config import...${NORMAL}"
echo
terminus remote:drush atdove."$REPLY_ENV" -- cim -y
echo
echo -e "${YELLOW}Clearing caches...${NORMAL}"
echo
terminus remote:drush atdove."$REPLY_ENV" cr
echo
#echo -e "${YELLOW}Disabling maintenance mode...${NORMAL}"
#echo
#terminus remote:drush atdove."$REPLY_ENV" state:set system.maintenance_mode 0
#echo

echo -e "${YELLOW}Running drush status so you know what's up...${NORMAL}"
echo
terminus remote:drush atdove."$REPLY_ENV" status
echo
