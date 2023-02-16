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
    lando drush cr
    lando drush migrate-reset-status atdove_assignments
    lando drush migrate-import atdove_assignments
    MIGRATE_RESULT=$?
    if [[ ${MIGRATE_RESULT} == 0 ]]; then
      echo ""
      echo "  [Migration] Yay. Migrate import succeeded after ${RUN_NUM} attempts. "
      echo ""
      break
    fi
  done