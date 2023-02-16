#continuous assignment migration
MAX_RUNS=100
RUN_NUM=0

while [[ ${RUN_NUM} -lt ${MAX_RUNS} ]];
    do
        # Pre-increment. The initial failed attempt was run number 1.
        (( ++RUN_NUM ))
    echo ""
    echo "  [Migration] Migrate-import run #${RUN_NUM} ..."
    echo ""
    lando drush migrate-reset-status atdove_users
    lando drush migrate-import atdove_users
    MIGRATE_RESULT=$?
    if [[ ${MIGRATE_RESULT} == 0 ]]; then
      echo ""
      echo "  [Migration] Yay. Migrate import succeeded after ${RUN_NUM} attempts. "
      echo ""
      break
    fi
  done