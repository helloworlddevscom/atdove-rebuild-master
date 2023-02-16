echo ""
echo "  Running every single migration script ..."
echo ""
lando drush migrate-status
lando drush migrate-import atdove_groups
lando drush migrate-import atdove_org_groups
./user_migration.sh
lando drush migrate-import atdove_taxonomy_migration
lando drush migrate-import atdove_node_blog
lando drush migrate-import atdove_node_ad
lando drush migrate-import atdove_node_announcement
lando drush migrate-import atdove_node_faq
lando drush migrate-import atdove_node_guides
lando drush migrate-import atdove_node_help_topic
lando drush migrate-import atdove_node_news_room
lando drush migrate-import atdove_node_news_and_events
lando drush migrate-import atdove_article_opigno_activity
lando drush migrate-import atdove_video_opigno_activity
lando drush migrate-import atdove_quiz_opigno_activity
lando drush migrate-import atdove_assignments
lando drush migrate-import atdove_blog_comments
lando drush migrate-import atdove_opigno_activity_comments
lando drush migrate-import atdove_flag_migration
lando drush migrate-import atdove_activity_flag_migration
lando drush migrate-import atdove_learning_paths
lando drush php-eval 'node_access_rebuild();'
