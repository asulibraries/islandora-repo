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

## Usage
- run node migration first, then file, then media
- you can migrate with drush like `drush migrate:import migration_id`
- you can also `rollback` and `reset` via drush
- when manipulating files in fedora, you MUST pass a userid that has permission fedoraAdmin like `--userid 1`

## Configuration

No configuration page is provided.


## Debugging tips
- run migrate drush commands with `--debug` flag
- run `drush migrate:messages migration_id` to see messges
- install migrate_devel module and run drush migrate command with `--migrate-debug` or `--migrate-debug-pre`

