<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Purpose](#purpose)
- [Project branch strategy](#project-branch-strategy)
- [Recommended versions](#recommended-versions)
- [Codebase](#codebase)
- [Hosting](#hosting)
- [External Resources](#external-resources)
- [Local dev setup using Lando](#local-dev-setup-using-lando)
  - [(Step 1) Get a Pantheon account](#step-1-get-a-pantheon-account)
  - [(Step 2) Install Lando and Docker Desktop](#step-2-install-lando-and-docker-desktop)
  - [(Step 3) Git clone the repo](#step-3-git-clone-the-repo)
  - [(Step 4) (Optional) Configure Git](#step-4-optional-configure-git)
  - [(Step 5) Configure /etc/hosts](#step-5-configure-etchosts)
  - [(Step 6) Lando start](#step-6-lando-start)
  - [(Step 7) Add SSH key to Pantheon and create machine token](#step-7-add-ssh-key-to-pantheon-and-create-machine-token)
  - [(Step 8) Pull production database to local](#step-8-pull-production-database-to-local)
  - [(Step 9) Run database updates, import config and clear caches](#step-9-run-database-updates-import-config-and-clear-caches)
  - [(Step 10) Test your site](#step-10-test-your-site)
  - [(Step 11) Login to site](#step-11-login-to-site)
- [Lando basics](#lando-basics)
- [Lando project specific commands](#lando-project-specific-commands)
  - [lando pull](#lando-pull)
  - [lando drupal-config-import](#lando-drupal-config-import)
  - [lando drupal-config-dev](#lando-drupal-config-dev)
  - [lando logs-drupal](#lando-logs-drupal)
  - [lando env-deploy](#lando-env-deploy)
  - [lando env-config-import](#lando-env-config-import)
- [Drush](#drush)
  - [Useful Drush commands](#useful-drush-commands)
    - [Clear caches](#clear-caches)
    - [Run database updates](#run-database-updates)
    - [Import config](#import-config)
    - [Export config](#export-config)
    - [List installed modules](#list-installed-modules)
- [Terminus](#terminus)
  - [Using Drush with Terminus](#using-drush-with-terminus)
- [Development process](#development-process)
  - [Drupal Configuration Synchronization](#drupal-configuration-synchronization)
    - [Importing config](#importing-config)
    - [Exporting config](#exporting-config)
  - [Pulling database to local](#pulling-database-to-local)
  - [Creating a new feature branch](#creating-a-new-feature-branch)
  - [Exporting config and committing code](#exporting-config-and-committing-code)
  - [Pushing a new branch](#pushing-a-new-branch)
  - [Creating a Pull Request/Creating a Pantheon Multidev](#creating-a-pull-requestcreating-a-pantheon-multidev)
  - [Writing QA Steps](#writing-qa-steps)
  - [Updating a Pull Request/Updating a Pantheon Multidev](#updating-a-pull-requestupdating-a-pantheon-multidev)
  - [Reviewing a Pull Request/Using Pantheon Multidev for QA](#reviewing-a-pull-requestusing-pantheon-multidev-for-qa)
  - [Merging a Pull Request](#merging-a-pull-request)
- [Theme](#theme)
  - [Node modules](#node-modules)
  - [Compiling Sass/SCSS to CSS](#compiling-sassscss-to-css)
  - [Minifying JS](#minifying-js)
  - [Gulp errors](#gulp-errors)
  - [Sass-lint and sass-lint-auto-fix](#sass-lint-and-sass-lint-auto-fix)
    - [Automatically fix Sass issues](#automatically-fix-sass-issues)
- [Lando, PhpStorm & Xdebug](#lando-phpstorm--xdebug)
- [Deployment](#deployment)
- [CircleCI](#circleci)
  - [Accessing the CircleCI Dashboard](#accessing-the-circleci-dashboard)
  - [Configuring CircleCI](#configuring-circleci)
  - [CircleCI TODO](#circleci-todo)
- [Updating Drupal core](#updating-drupal-core)
- [Updating a contrib module](#updating-a-contrib-module)
- [Installing a contrib module](#installing-a-contrib-module)
- [Patching Drupal core or a contrib module](#patching-drupal-core-or-a-contrib-module)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Purpose

Drupal 9 Opigno site for AtDove (2021 rebuild).

# Project branch strategy
    master                            :: Push automatically deploys to dev env. May also currently be deployed to test and prod based on tag. Pantheon does not have separate branches for dev, test and prod.
    ar-ticket-number-description      :: Feature branch corresponding to a Jira ticket. Branched off of master. Create a PR to merge back into master. May also correspond to a Pantheon Multidev env.

# Recommended versions

The recommended and documented way to run this site is using Lando. With Lando you don't have to worry about installing correct versions so long
as you preface most commands with `lando`. Still, it's nice to know what versions Lando installs itself. Note that the PHP version is actually
configured in `pantheon.yml` (not `.lando.yml`) and because we're using the Lando pantheon recipe, Lando knows to use the version set there.

    PHP         :: 7.3 (Installed automatically by Lando)
    Node        :: 12.0.0 (Installed automatically by Lando) @TODO: Not using Node yet.
    Composer    :: 2.x (Installed automatically by Lando)


# Codebase

There are two remotes for this project: GitHub and Pantheon. GitHub should be treated as the source of truth, though they should be kept in sync by CircleCI.

* GitHub: https://github.com/HelloWorldDevs/atdove-rebuild

# Hosting

This site is hosted on Pantheon. Pantheon provides a Dashboard from which you can manage deploys, create database backups etc.

* Pantheon Dashboard: https://dashboard.pantheon.io/sites/4f02c1a3-b6fa-4ddb-bc28-36df3902c207#dev/code
* Environments:
  * Dev: http://dev-atdove.pantheonsite.io/
  * Test: http://test-atdove.pantheonsite.io/
  * Prod/Live: http://live-atdove.pantheonsite.io/
  * Pantheon Multidevs: https://dashboard.pantheon.io/sites/4f02c1a3-b6fa-4ddb-bc28-36df3902c207#multidev
    * These are envs that correspond to feature branches and/or PRs. You can create them manually but they are also created automatically by CircleCI.
    * We use Multdev envs for QA.

# External Resources

* Pantheon Dashboard: https://dashboard.pantheon.io/sites/4f02c1a3-b6fa-4ddb-bc28-36df3902c207#dev/code
* CircleCI Dashboard: https://app.circleci.com/pipelines/github/HelloWorldDevs/atdove
* Jira: https://helloworlddevs.atlassian.net/jira/software/c/projects/AR/boards/96
* Confluence: https://helloworlddevs.atlassian.net/wiki/spaces/AR/overview


# Local dev setup using Lando

## (Step 1) Get a Pantheon account

Ask another developer to invite you to the Pantheon team.

## (Step 2) Install Lando and Docker Desktop

Follow the recommended instructions for installing Lando if you haven't already. Docker Desktop will be installed as well.

https://docs.lando.dev/basics/installation.html#macos

## (Step 3) Git clone the repo

`git clone` the GitHub repo into whichever directory you prefer.

`cd` into the repo/project root.

## (Step 4) Configure /etc/hosts

Edit `/etc/hosts` on your local machine. Add this line:

```
127.0.0.1				atdove-rebuild.lando
```

Normally Lando will create site URLs in the format *.lndo.site. Because of the proxy settings we have in `.lando.yml`, those won’t be created. Instead we’ll have this nicer URL, but the trade off is we have to add it to our `/etc/hosts` file.

## (Step 5) Lando start

Run:

```
lando start
```

The first time you run this it will take a while. Eventually you’ll be given some URLs to access the site, however they will not work yet because we haven’t pulled the database yet. Lando attempts to create URLs based on the available ports on your machine. The URLs that will be most reliable/consistent will be https://atdove-rebuild.lando or http://atdove-rebuild.lando, however you may find that they are instead https://atdove-rebuild.lando:444/ or http://atdove-rebuild.lando:8000/. Make note of whatever URLs you’re given so we can try them later.

## (Step 6) Add SSH key to Pantheon and create machine token

Follow Pantheon's documentation:

* Add your SSH public key to your Pantheon account.
  * https://pantheon.io/docs/ssh-keys#add-your-ssh-key-to-pantheon
* Create a Machine Token in your Pantheon account.
  * https://pantheon.io/docs/machine-tokens#create-a-machine-token
  * Copy and paste the machine token somewhere so you can use it later.
  * It would make sense to name the token "Atdove - Lando".
  * When asked to run a `terminus` command to authenticate, preface it with `lando terminus`.

## (Step 7) Pull "production" database to local

As of 10/18/21, we are not using the Test or Live environments. Dev represents the most recent version of Catherine's local database, so that is the one you should pull.
To pull the database from a remote Pantheon environment, run:

```
lando pull
```

* You may be asked to authenticate using the machine token you created.
* You'll be asked whether/where you want to pull code, database and files from. You can select "none" for anything you want to skip.
  * Choose "none" for code (use `git pull` instead).
  * Choose "dev" for database.
  * Choose "none" for files. (you may be able to get by without files and save some space on your machine)

## (Step 8) Run composer install, database updates, import config and clear caches

To install dependencies via Composer, run:

```
lando composer install
```

To run database updates, import config and clear caches, run:

```
cd web
```

```
lando drupal-config-import
```

This is a wrapper around `drush updb -y; drush cim -y; drush cr`. You could call these directly instead.

_Always run one of these commands after pulling the database from a remote environment._

## (Step 9) Test your site

Go to one of the URLs you were given by `lando start`. If using Chrome you may have to use the “Advanced” option to bypass the SSL warning. For some reason Chrome does not like the way Lando signs SSL certificates. You will only have to do this the first time you visit the site after running lando start.

## (Step 10) Login to site

* Login to site by going to /user
* To get the credentials, search LastPass for "atdove rebuild lando".

# Lando basics

https://helloworlddevs.atlassian.net/wiki/spaces/HWD/pages/1635418113/Lando+basics

# Lando project specific commands

Run `lando` for a full list of commands.

## lando pull
```
lando pull
```
Provided by Lando Pantheon recipe.

Pulls the database or files from a remote Pantheon environment.

## lando drupal-config-import
```
lando drupal-config-import
```
Runs database updates, imports config and clears caches on your local Lando environment.

Run this after pulling the database and/or before starting a new ticket.

## lando drupal-config-dev
```
lando drupal-config-dev
```
Enables development modules on your local Lando env using config_split module.

Useful if you need Devel and Kint modules enabled.

## lando logs-drupal
```
lando logs-drupal
```
Tails Drupal logs using syslog module.

Useful because Drush 9 removed the ability to tail `drush wd-show`.

## lando env-deploy
```
lando env-deploy
```
Deploys to Test or Live Pantheon environment (deploys code, updates database, imports config and clears caches).

## lando env-config-import
```
lando env-config-import
```
Runs database updates, imports config and clears caches on a remote Pantheon environment.

# Drush

_NOTE: In order to run a Drush command, you must be in `/web`._

Drush is a CLI tool for Drupal. Lando installs it automatically and you can use it by running:

```
lando drush COMMAND
```

## Useful Drush commands

### Clear caches

```
lando drush cr
```

### Run database updates

```
lando drush updb -y
```

### Import config

```
lando drush cim
```

### Export config

```
lando drush cex
```

### List installed modules

```
lando drush pm-list
```

# Terminus

Terminus is a CLI tool for Pantheon. Lando installs it automatically and you can use it by running:

```
lando terminus
```

It's likely you'll never have to use Terminus directly. You'll hopefully find that you can accomplish what you need by running `lando` and choosing a command from the list.

## Using Drush with Terminus

You can use Terminus to run Drush commands on different Pantheon environments. The possible environments are dev, test, live or a Multidev environment.

For example, to run a Drush command on the dev environment:

```
terminus drush atdove.dev -- DRUSH_COMMAND
```

# Development process

## Refresh your local

You want to pull a fresh copy of code everytime you start a new ticket to ensure you are merging all the latest changes. At least once a week you should pull the DB of record (currently dev, but soon to be prod) and the files.

## Composer install

Remember to run `lando composer install` to install depedencies any time you pull another developers code to your local.

## Drupal Configuration Synchronization

See documentation in HW Confluence:

https://helloworlddevs.atlassian.net/l/c/tYCo1PA9

### Importing config

To import config, run:

```
lando drush cim
```

After pulling the database and before starting a new ticket, **it is extremely important to import config**. This will ensure that if you later export config, you are not accidentally deleting another developer's work.

You can also import config by running:

```
lando drupal-config-import
```

This also runs database updates and clears caches.

### Exporting config

To export config, run:

```
lando drush cex
```

After you've completed a ticket locally that involved any configuring of Drupal, you should export config. Check `git status` after running the config export and **be very careful to only commit changes that you intended to make.** If you do not recognize the change, most likely it is undoing another developer's work (because you forgot to run config import before starting your work).

## Pulling database to local

Run:
```
lando pull
```
* Choose "none" for code.
* Choose "live" for database.
* Choose "none" for files.

## Creating a new feature branch

* `git checkout master; git pull;`
* [Pull a fresh copy of the Prod DB](#pulling-database-to-local).
* Run `lando drupal-config-import`. This will run database updates, import config and clear caches. It will import any config changes that have been committed by another developer on the `master` branch.
* Run `git checkout -b ro-TICKET_NUMBER` to create a new feature branch.

## Exporting config and committing code

* Commit your code changes. Please start your commit message with "ar-TICKET_NUMBER:".
* Run `lando drush cex` to export config. Drupal will compare your database to the config yml
files and modify or create new yml files accordingly.
* Inspect the config changes _carefully_ to ensure that you are not committing any changes
that you did not intend to make.
* Commit your config changes.

## Pushing a new branch

If you modified your `.git/config`, when you run `git push` to push
a new branch for the first time, you may not be given a helpful message.
Here is the pattern to follow to push a new branch and set up tracking:
`git push -u github ar-15-retina-images`

## Creating a Pull Request/Creating a Pantheon Multidev

You can create PRs using GitHub: https://github.com/HelloWorldDevs/atdove-rebuild/pulls

Ensure that you branched off of `master` to create your feature branch,
and that your PR merges your feature branch back into `master`.

Whenever a PR is opened on GitHub, CircleCI will build a Pantheon Multidev environment using the code from your feature branch.
You can monitor the build process by clicking the "Details" link next to the CircleCI logo.

When the CircleCI build is complete, you should be able to access a new Pantheon Multidev environment.
Check the [Pantheon dashboard](https://dashboard.pantheon.io/sites/4f02c1a3-b6fa-4ddb-bc28-36df3902c207#multidev/dev-environments) and look for the most recent one.
They are named based on the PR number. The database and files will be cloned from the "dev" environment.

In the review steps in your PR, include a link to the Pantheon Multidev environment. In many cases whoever reviews the PR can use the Multidev environment to QA instead of doing it locally.
If you don't want to wait for the build to complete to get the URL to the Multidev, you can guess what it will be based on the PR number. It will follow a pattern like:

http://pr-20-atdove.pantheonsite.io/

## Writing QA Steps

Post QA Steps on the PR if you intend for it to be code reviewed by another developer. Post QA Steps on the ticket for the project manager or client to follow on the Multidev.

There is a Multidev Link field on the ticket you can update with the correct URL to the Multidev.

## Updating a Pull Request/Updating a Pantheon Multidev

If you push a new commit to your feature branch it will trigger the Multidev to be rebuilt. Check the CircleCI dashboard to monitor the progress.

## Reviewing a Pull Request/Using Pantheon Multidev for QA

If you've been asked to review a PR, you can use the Multidev environment to QA instead of pulling the branch and doing it locally.

When you're done with code review/QA:

* _DO NOT MERGE THE PR YET_.
* Move the related Jira ticket to the QA column.
* Ensure that the original dev has posted QA Steps on the ticket.
* Wait for project manager or client to QA on Multidev environment.
* Once ticket passes QA, the original dev can merge the PR.

## Merging a Pull Request

After PR has passed code review (if requested) and QA by the project manager or client on the Multidev environment, click the "Merge pull request" button in the GitHub PR.
CircleCI will push the code to the Pantheon repo, trigger a deploy to the Dev environment.
The Multidev environment will eventually be automatically deleted to make room for another Multidev.

DO NOT merge a Multidev from the Pantheon dashboard. The merge will not be pushed back to GitHub.
@TODO: Look into https://github.com/pantheon-systems/quicksilver-pushback

# Theme

The frontend Drupal theme is located at `web/themes/custom/atdove`.

## Node modules

@TODO: Node is not installed or used yet.
Lando installs Node and Gulp and runs `npm install` within the `node` container/service the first time you run `lando start`,
or whenever you run `lando rebuild`. You do not need to run `lando npm install` yourself, but you can if you want.
So long as you preface Gulp commands with `lando` you don't need to worry about switching your local environment to use the correct Node version.

## Compiling Sass/SCSS to CSS

@TODO: We don't use sass yet.
To compile, run from `web/themes/custom/atdove`:

```
lando gulp build
```

To compile and watch, run from `web/themes/custom/atdove`:

```
lando gulp watch
```

## Minifying JS

To minify, run from `web/themes/custom/atdove`:

```
lando gulp build
```

To minify and watch, run from `web/themes/custom/atdove`:

```
lando gulp watch
```

## Gulp errors

If Gulp commands fail, ensure that you are prefacing the command with `lando`.

Make sure you are in `web/themes/custom/atdove`.

## Sass-lint and sass-lint-auto-fix

Sass-lint and sass-lint-auto-fix are used to lint and automatically fix Sass files.
These are configured by their respective files in the `atdove` theme:
* `.sass-lint.yml`
* `.sass-lint-auto-fix.yml`

To run sass-lint and get a report of issues found, run from `web/themes/custom/atdove`:

```
lando gulp lint-scss
```

or

```
lando npm run lint -s
```

### Automatically fix Sass issues

@TODO: This has stopped working. See: https://www.npmjs.com/package/sass-lint-auto-fix

To automatically fix Sass issues that were found, run from `web/themes/custom/atdove`:

```
lando npm run lint:fix
```


# Lando, PhpStorm & Xdebug

See documentation in HW Confluence:

https://helloworlddevs.atlassian.net/wiki/spaces/HWD/pages/899416079/Step+debugging+with+Lando+PhpStorm+Xdebug+and+Drupal


# Deployment

For documentation of deployment processes, see this [Confluence page](https://helloworlddevs.atlassian.net/wiki/spaces/RO/pages/898203652/Deploy+Process).

# CircleCI

This project uses CircleCI to push commits from GitHub to Pantheon. Because of CircleCI we can use
GitHub as our primary repo and the Pantheon repo will be kept in sync. Whenever a PR is opened on GitHub, CircleCI will build
a Pantheon Multidev environment to using the code from the PR. When a PR is merged into `master`, CircleCI will handle pushing
those commits to Pantheon and deploying to the Dev environment.

NOTE: CircleCI is NOT involved in deploying to Test or Live/Prod. This is because the purpose of CircleCI was to facilitate pushing code
from GitHub to Pantheon without using any `.git/config` hijinks and to encourage the use of Multidevs for QA. Dev, Test and Live all share
the `master` branch on Pantheon, which means after the code has been merged into `master` and deployed to Dev, GitHub is no longer involved in the deploy process.

@TODO: If we need merges into other branches to trigger CircleCI, we may need to disable
"Only build pull requests" [here](https://app.circleci.com/settings/project/github/HelloWorldDevs/atdove-rebuild/advanced?return-to=https%3A%2F%2Fapp.circleci.com%2Fpipelines%2Fgithub%2FHelloWorldDevs%2Fatdove).
This would significantly increase the amount of builds that are performed because it means a build happens _every time any branch is pushed to_.

In conjunction with CircleCI, a Pantheon QuickSilver script runs database updates, imports config and clears caches after
certain Pantheon workflows occur. This is configured in `pantheon.yml` and `web/private/scripts/quicksilver/drush_update_import/drush_update_import.php`.

## Accessing the CircleCI Dashboard

The CircleCI Dashboard can be found here:

https://app.circleci.com/pipelines/github/HelloWorldDevs/atdove

## Configuring CircleCI

If you need to adjust the CircleCI settings, you can do so with a combination of using the CircleCI dashboard and modifying `.circleci/config.yml`.

We're using Pantheon's CircleCI orb which sets us up with the Multidev creation build process quickly. If you have questions about how
this is all working, the documentation for that is the place to look first:

https://github.com/pantheon-systems/circleci-orb

It's possible in the future we'll need to some more complicated CI work, and will need to refactor to use: https://github.com/pantheon-systems/example-drops-8-composer

## CircleCI TODO

Here's some things we'd like to add:

* Slack notification when build is complete with link to Multidev environment.
* Compile SCSS to CSS. There's an example of this here: https://github.com/pantheon-systems/circleci-orb
* Name Multidev environments based on feature branch name instead of PR number. This would make it more QA friendly.

# Updating Drupal core

Drupal core and contrib modules should be updated using Composer.

@TODO: Not sure this is correct. How do we update core with Opigno?
For this project, core can be updated by running:

`lando composer update drupal/core-recommended --with-dependencies`

See documentation in HW Confluence (but always run Composer commands using `lando composer`):

https://helloworlddevs.atlassian.net/l/c/tamEHpst

# Updating a contrib module

See documentation in HW Confluence (but always run Composer commands using `lando composer`):

https://helloworlddevs.atlassian.net/l/c/GRzuiByr

# Installing a contrib module

See documentation in HW Confluence (but always run Composer commands using `lando composer`):

https://helloworlddevs.atlassian.net/l/c/0fknkL3T

# Patching Drupal core or a contrib module

See documentation in HW Confluence (but always run Composer commands using `lando composer`):

https://helloworlddevs.atlassian.net/l/c/PUHQ1DhR

#
