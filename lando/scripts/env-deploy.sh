#!/usr/bin/env bash

# This file is configured in .lando.yml. Run: lando env-deploy
# It will trigger a deployment to the chosen environment and
# then runs database updates, imports config and clears caches.
# You cannot choose the dev environment because it is not necessary. The dev
# environment is deployed to whenever the master branch is pushed to, and
# database updates, config import and cache clear is handled automatically
# by web/private/scripts/quicksilver/drush_update_import/drush_update_import.php.

NORMAL="\033[0m"
RED="\033[31m"
YELLOW="\033[32m"
ORANGE="\033[33m"
PINK="\033[35m"
BLUE="\033[34m"

echo
echo -e "${RED}WARNING: This will affect a remote Pantheon environment."
echo
echo -e "${ORANGE}Are you sure you want to deploy to a remote Pantheon environment? (y/n):${BLUE}"
echo
read REPLY
if [[ ! "$REPLY" =~ ^[Yy]$ ]]
then
  exit 1
fi

echo
echo -e "${ORANGE}Enter the environment to deploy to (test/live):${BLUE}"
echo
read REPLY_ENV
if [ "$REPLY_ENV" = "live" ] || [ "$REPLY_ENV" = "test" ];
then
  if [ "$REPLY_ENV" = "live" ];
  then
    echo
    echo -e "${ORANGE}Are you sure you want to deploy to and run database updates, import config and clear caches on prod? This will wipe out any config changes that are currently on prod. (y/n):${BLUE}"
    echo
    read REPLY_CONFIRM
    if [[ ! "$REPLY_CONFIRM" =~ ^[Yy]$ ]]
    then
      exit 1
    fi
  fi

  echo
  echo -e "${ORANGE}Optionally enter a deploy message:${BLUE}"
  echo
  read REPLY_MESSAGE
  if [ -n "$REPLY_MESSAGE" ]
  then
    DEPLOY_MESSAGE="$REPLY_MESSAGE"
  else
    DEPLOY_MESSAGE=""
  fi

  echo
  echo -e "${YELLOW}Deploying to $REPLY_ENV env...${NORMAL}"
  echo
  terminus env:deploy atdove."$REPLY_ENV" --note "$DEPLOY_MESSAGE"

  echo
  echo -e "${YELLOW}The Quicksilver script should have already ran database updates, config import, reindexed search and cleared caches after the deploy,${NORMAL}"
  echo -e "${YELLOW}but we'll run them again just to double check. If you see no pending updates or config to import DON'T WORRY, everything worked as planned!${NORMAL}"
  echo
  echo -e "${YELLOW}Running database updates, config import and clearing caches on $REPLY_ENV env...${NORMAL}"
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
  echo -e "${YELLOW}Reindexing search...${NORMAL}"
  echo
  terminus remote:drush atdove."$REPLY_ENV" search-api-clear main
  terminus remote:drush atdove."$REPLY_ENV" search-api-index main
  echo
  echo -e "${YELLOW}Clearing caches one last time...${NORMAL}"
  echo
  terminus remote:drush atdove."$REPLY_ENV" cr
  echo
  echo -e "${YELLOW}Running drush status so you know what's up...${NORMAL}"
  echo
  terminus remote:drush atdove."$REPLY_ENV" status
  echo
else
  echo
  echo -e "${RED}Please provide a valid env to deploy to. Choose between 'test' or 'live'. You can not deploy to dev env or a multidev env.${NORMAL}"
fi
