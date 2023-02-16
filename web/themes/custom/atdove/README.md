# Building assets (SCSS to CSS, minifying JS etc.)
We use Gulp to build our assets.

## To build once
```
cd web/themes/custom/atdove && lando gulp build
```
OR
```
cd web/themes/custom/atdove && lando npm run build
```
OR
```
lando assets-build
```

## To build once and then watch for changes
```
cd web/themes/custom/atdove && lando gulp watch
```
OR
```
cd web/themes/custom/atdove && lando npm run watch
```
OR
```
lando assets-watch
```

# Aristotle theme SCSS
The base theme is Aristotle. Aristotle is built on Bootstrap and uses SCSS.

Rather than overriding the _compiled_ CSS from Aristotle, we are importing the SCSS in `web/themes/custom/atdove/src/scss/main.scss`.
See comments there for more information. This gives us access to override variables set by Aristotle and Bootstrap before they are compiled.

In `web/themes/custom/atdove/package.json` a postinstall script is set. The script rewrites image and font paths in Aristotle theme SCSS to be relative to
`web/themes/custom/atdove/src/scss/main.scss`. This allows the images and fonts to be found within `web/themes/contrib/aristotle`.

# To Do
* Ignore `web/themes/custom/atdove/dist/` and run `npm run build` as part of CircleCI build.
* Add image minification.


