# Silver
Davao's movies in one place.

## Developing
1. Install [Composer](http://getcomposer.org).
2. Download Silver's dependencies by doing a `composer install`.
3. Install [Robo](http://robo.li) and then do a first time setup `robo unpack`.

## Robo Reference
Silver uses [Robo](http://robo.li) to perform some tasks (e.g. `robo fetch`).

* `robo compile <all|css|js>` - Concatenates and minifies assets through gulp.
`css` or `js` can be added to compile only CSS files or Javascript files.

* `robo fetch <mall>` - Fetches screening schedules from all the malls and
saves them to the database. A mall parameter can be added to fetch only from
the said mall.

* `robo prepare` - Prepares the app for deployment to Google App Engine. Will
run the `test` suite then `compile` assets.

* `robo seed` - Seeds the database, inserting initial app records.

* `robo test` - Runs the test suite.

* `robo update <all|info|scores>` - Updates movie data. An optional `info`
or `scores` can be added to just update movie information or scores only.

* `robo unpack` - Performs first time setup (`seed`, `compile`) so the app is
ready to be used.

## Deployment
Prepare the app first by doing a `robo prepare` and then deploy the app using
the Google App Engine launcher.