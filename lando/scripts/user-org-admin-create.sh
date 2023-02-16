#!/usr/bin/env bash

# This file is configured in .lando.yml. Run: lando user-org-admin-create.
# It will create a new user with Admin role within a new Organization group.

NORMAL="\033[0m"
RED="\033[31m"
YELLOW="\033[32m"
ORANGE="\033[33m"
PINK="\033[35m"
BLUE="\033[34m"


/app/vendor/bin/behat --config /app/tests/behat/behat.yml --suite notest /app/tests/behat/notest/create_test_users.feature
echo
echo -e "${YELLOW}Org Admin user created with username/email 'test-org-admin@helloworlddevs.com' & password 'wh0C4r3s?'.${NORMAL}"
echo -e "${YELLOW}Organization group created with name 'Test Organization'.${NORMAL}"
