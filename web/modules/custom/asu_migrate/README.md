# ASU Migration Module

## Introduction

This repository, __asu_migrate__, provides a sample migration as well as the beginnings of the ETD migration.

This repository also contains a `data` folder containing a CSV and sample images, as a convenience so that the accompanying files are easily available on the Drupal server running the migration. (This is not the recommended method for making files available to Drupal in a real migration.)


## Requirements

This module requires the following modules:

* [drupal/migrate_source_csv](https://www.drupal.org/project/migrate_source_csv)

## Installation

- `drush en -y asu_migrate` to enable the module, installing the migrations as configuration.

Optionally, flush the cache (`drush cr`), so the migrations become visible in the GUI at Manage > Structure > Migrations > asu_migrate (http://localhost:8000/admin/structure/migrate/manage/asu_migrate/migrations)


## Configuration

No configuration page is provided.

