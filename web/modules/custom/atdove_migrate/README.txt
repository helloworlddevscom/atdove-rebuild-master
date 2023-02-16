MIGRATION NOTES
_______________

Find all migration scripts in run-all-migrations.sh. 
Execute like `./run-all-migrations.sh`
You can either run this or run each line individually. You must run `lando drush migrate-status` first for the commands to be installed.

A fresh copy of the Production database will work with the migration, however, you either need to enable the h5p module before exporting, OR copy over the `h5p_libraries` table directly from a previous database that has it. This is a formality, we are not actually migrating anything from this table.

--------------------------
Estimated Migration Times
--------------------------
atdove_node_blog 				9m12.903s
atdove_groups 					13m53.049s
atdove_org_groups 				9m12.879s + 8m40.178s
atdove_users 					24m40.928s(15) (50%)
atdove_node_ad 					0m3.904s
atdove_node_announcement		0m4.527s
atdove_node_faq					0m3.584s
atdove_node_guides 				0m3.410s
atdove_node_help_topic			0m4.861s
atdove_node_news_room			0m4.584s
atdove_node_news_and_events		0m3.134s
atdove_article_opigno_activity 	8m38.902s
atdove_video_opigno_activity    12m41.399s
atdove_quiz_opigno_activity		1m23.807s
atdove_assignments (1000)		6m
atdove_blog_comments			1m37.074s
atdove_opigno_activity_comments	2m52.953s
atdove_flag_migration			22m6.555s (50%)
atdove_learning_paths           18m59.555s (50%)


--------------------------
ROLLING MIGRATION
--------------------------

1) run `lando db-import [previously migrated database where you left off]`
2) Go to your database management software (SQL Ace) and import the latest Production Database into the 'migrationdb' database (Download from the pantheon dashboard)
3) Import the single table 'h5p_libraries' into the 'migrationdb' database
4) run `lando drush migrate-status`
5) run each migration in the order they appear in the run-all-migrations.sh