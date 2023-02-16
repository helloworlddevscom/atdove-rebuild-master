#!/usr/bin/env bash

# This file is configured in .lando.yml. Run: lando user-admin-reset.
# It will reset the password for whatever admin user exists.

NORMAL="\033[0m"
RED="\033[31m"
YELLOW="\033[32m"
ORANGE="\033[33m"
PINK="\033[35m"
BLUE="\033[34m"

echo
echo -e "${ORANGE}Enter new password for Administrator user:${BLUE}"
echo
read PASSWORD
if [ -n "$PASSWORD" ]
then
  PASSWORD="$PASSWORD"
else
  PASSWORD="password"
fi

drush upwd admin "$PASSWORD" || drush upwd asolomon@dovelewis.org-old "$PASSWORD";

echo
echo -e "${YELLOW}Password has been reset.${NORMAL}"
echo
echo -e "${YELLOW}If you have not run migrations, the username/email is: admin ${NORMAL}"
echo
echo -e "${YELLOW}If you have run migrations, the username/email is: asolomon@dovelewis.org-old ${NORMAL}"
