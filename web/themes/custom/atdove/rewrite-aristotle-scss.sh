#!/usr/bin/env bash

# Rewrites SCSS files in Aristotle base theme so
# image paths are relative to web/themes/custom/atdove/src/scss/main.scss
# The Aristotle theme SCSS can then be compiled by our AtDove theme,
# giving us a chance to overwrite variables defined by Aristotle.

find ../../contrib/aristotle/src/scss -type f -exec sed -i -e 's/url(..\/..\/images/url(..\/..\/..\/..\/contrib\/aristotle\/src\/images/g' {} \;
find ../../contrib/aristotle/src/scss -type f -exec sed -i -e 's/url(..\/images/url(..\/..\/..\/..\/contrib\/aristotle\/src\/images/g' {} \;
find ../../contrib/aristotle/src/scss -type f -exec sed -i -e 's/url(..\/..\/fonts/url(..\/..\/..\/..\/contrib\/aristotle\/src\/fonts/g' {} \;
find ../../contrib/aristotle/src/scss -type f -exec sed -i -e 's/url(..\/fonts/url(..\/..\/..\/..\/contrib\/aristotle\/src\/fonts/g' {} \;
find ../../contrib/aristotle/src/scss -type f -exec sed -i -e 's/url("..\/fonts/url("..\/..\/..\/..\/contrib\/aristotle\/src\/fonts/g' {} \;
