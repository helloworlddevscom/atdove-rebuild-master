#!/usr/bin/env bash

drush sql-drop -y
composer install
drush site:install -y

MAX_RUNS=6
RUN_NUM=0

while [[ ${RUN_NUM} -lt ${MAX_RUNS} ]];
    do
        # Pre-increment. The initial failed attempt was run number 1.
        (( ++RUN_NUM ))
    echo ""
    echo "  [Lando] Config-import run #${RUN_NUM} ..."
    echo ""
    drush config:import -y
    IMPORT_RESULT=$?
    if [[ ${IMPORT_RESULT} == 0 ]]; then
      echo ""
      echo "  [Lando] Yay. Config import succeeded after ${RUN_NUM} attempts. "
      echo ""
      break
    fi
  done

drush updatedb -y
drush php-eval 'node_access_rebuild();'
drush user-add-role "administrator" admin
drush uli
