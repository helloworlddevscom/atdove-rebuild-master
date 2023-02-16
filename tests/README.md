# Behat with Lando
Lando has been configured to work with Behat and will be customized to work with
the atdove-rebuild project. Here are some general helpful tips for working with behat.

####To run all tests:
`lando behat`

####To run tests with tags:
`lando behat --tags '@current'`

####To run tests without tags:
`lando behat --tags '~@current'`

####To run tests with multiple tags:
`lando behat --tags '~@current&&@profiles'`

`lando behat --tags '~@current&&~@notreadyyet'`

####To test a single behat feature file:
`lando behat /app/tests/behat/features/stripe_webhooks.feature`

####To test a single scenario in a single behat feature file:
`lando behat /app/tests/behat/features/site_general_functionality.feature:22`

####Get a list of all step definitions:
`lando behat -dl`

##Testing with Chrome & Javascript and screenhots

Any test prefaced with the `@javascript` tag will use
 browser emulation provided by the selenium-chrome docker image.

If a test fails, a sreenshot of the failure will be placed in `(docroot)/tests/reports/` for you to review. The file will be named `features_FILE_NAME_feature_LINENUMBER` with linenumber
indicating the line at which the `Scenario:` that failed begins.
