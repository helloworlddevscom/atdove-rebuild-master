#!/usr/bin/env bash

# Run by config-dev-disable. Also run on post-pull event.
# This ensures that if db is pulled and config is exported before
# being imported, config to enable dev modules is not accidentally exported.

sed -i "s/config_split.config_dev_devel']\['status'] = TRUE/config_split.config_dev_devel']\['status'] = FALSE/" /app/web/sites/default/settings.local.php
sed -i "s/config_split.config_dev_syslog']\['status'] = TRUE/config_split.config_dev_syslog']\['status'] = FALSE/" /app/web/sites/default/settings.local.php
