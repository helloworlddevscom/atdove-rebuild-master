#!/usr/bin/env bash

# This file is configured in .lando.yml. Run: lando user-admin-create.
# It will create a new user with Administrator role.

NORMAL="\033[0m"
RED="\033[31m"
YELLOW="\033[32m"
ORANGE="\033[33m"
PINK="\033[35m"
BLUE="\033[34m"


echo
echo -e "${ORANGE}Enter password for new Administrator user:${BLUE}"
echo
read PASSWORD
if [ -n "$PASSWORD" ]
then
  PASSWORD="$PASSWORD"
else
  PASSWORD="password"
fi

drush ucrt test-admin@helloworlddevs.com --mail="test-admin@helloworlddevs.com" --password="$PASSWORD";
drush urol administrator test-admin@helloworlddevs.com;
drush uublk test-admin@helloworlddevs.com;

echo
echo -e "${YELLOW}Administrator user created with username/email 'test-admin@helloworlddevs.com'${NORMAL}"
