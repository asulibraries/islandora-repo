# ASU Digital Repository on Islandora8
This repository is a drupal root for ASU Digital Repository built using [Islandora 8](https://islandora.ca/). ([Islandora Documentation](https://islandora-claw.github.io/CLAW/))

For development purposes, this repository should be integrated with the [islandora provided vagrant environment](https://github.com/Islandora-Devops/claw-playbook).

It will also include ansible scripts for provisioning and deploying to additional environments.

# Local Development Setup
0. Install dependencies
    a. VirtualBox version 5.whatever (not 6.0)
    b. Vagrant (tested up to version 2.1.2)
    c. git
    d. ansible
    e. vagrant vbguest plugin (`vagrant plugin install vagrant-vbguest`)
1. Go to the [ASU claw-playbook repo](https://github.com/asulibraries/claw-playbook)
2. Clone ASU claw-playbook
3. cd into claw-playbook
4. Make a file called in your user root called .asurepo_vault_pass and get it from the lastpass. (this is the password for decrypting ansible vault stuff which will allow you to deploy to create and encrypt files)
5. Run vagrant up (from within the claw-playbook root)
6. This repo will be available inside the vagrant VM as `/var/www/html/drupal`
7. If you want the ASU specific config, cd into `/var/www/html/drupal` and run `drupal config:import --directory /var/www/html/drupal/config/sync`

# Ansible
If you've already provisioned your vagrant environment and need to re-run the ASU specific provisioning, you can do so with `ansible-playbook asu-install.yml -i inventory/vagrant -l all -e ansible_ssh_user=$vagrantUser -e islandora_distro=ubuntu/xenial64` Your $vagrantUser will either be ubuntu or vagrant. Check to see what user you become when you `vagrant ssh`.


# Helpful Hints
If you need to update your ansible roles (to get updated versions of the packages), you mine as well `rm -rf roles/external` and `vagrant provision` to fix that. This will take some time.

Understanding how drupal entities relate to fedora objects - https://drive.google.com/file/d/1Ra64mFAsHkPtAf-2BWjdYKDv1Fc2uJSU/view

Get the json-ld for an object in Drupal like so : http://localhost:8000/node/1?_format=jsonld

# Updating an existing install
1. pull down updated claw-app (`cd /var/www/html/drupal && git pull`)
2. drupal config:import like `drupal config:import --directory /var/www/html/drupal/config/sync`
3. cd into web directory
3. run database migrations - `drush updatedb`
4. clear drupal cache - `drush cache-rebuild`
5. composer updates?

## So you want to add a module
1. Add the module to the composer requirements in the ASU specific ansible role
2. Add the module to the drush enabling in the ASU specific ansible role
3. Run the ASU specific ansible role

## So you want to update an existing Islandora/Drupal Site
1. pull down the most recent claw-playbook code from github
2. pull down the most recent isladora-repo code from github (in claw-sandbox folder)
3. examine your config sync and see if you have things you either want to export and commit or don't care if you lose them - http://localhost:8000/admin/config/development/configuration (see Tips for Config Syncing below)
4. vagrant ssh and cd to /var/www/html/drupal, run composer install
5. cd /var/www/html/drupal/web, run drush config:import (note that if your step 3 showed that you have config changes in your DB that aren't in code, those would get wiped away by a config:import)
6. drush udpatedb - to update the database
7. drush cache-rebuild - to clear the cache

## Tips on Config Syncing
* To export content, go to your drupal root such as `/var/www/html/drupal` and run `drupal config:export --directory config/sync --remove-uuid --remove-config-hash` ([see](https://hechoendrupal.gitbooks.io/drupal-console/content/en/commands/config-export.html))
* To import content, go to your drupal root such as `/var/www/html/drupal` and run `drupal config:import --directory /var/www/html/drupal/config/sync` ([see](https://hechoendrupal.gitbooks.io/drupal-console/content/en/commands/config-import.html))
* To export the configs split based on environments, use `drupal config_split:export --split config_split.config_split.environment_config`
* To import the split config (or any one part of config), use `drush config:import --partial --source=/the/path/`

## Tips on Using Drush
[Drush full command list](https://drushcommands.com/drush-9x/)
Common Commands
* `drush cache-rebuild` - clear cache
* `drush pm:enable module_name` - enable module
* `drush pm:uninstall module_name` - disable module

## Tips on Using Composer
* To install everything from a composer.json file - `composer install`
* To add a package `composer require packagename`
* To update a package `composer update packagename`


# Deploying to AWS
1. `pip install boto boto3`
2. run `ansible-playbook aws_create_multiple_ec2.yml`
3. locally run `ansible-galaxy install -r requirements.yml`
4. locally run `ansible-playbook -i inventory/stage playbook.yml -e "islandora_distro=ubuntu/xenial64" -e @inventory/stage/group_vars/all/passwords.yml -e @aws_keys.yml`
<!-- must have an IAM role and key with privileges to administer EC2 -->
- The approach I've taken thus far is to create 2 EC2 instances in the following breakdown:
  - webserver - for the actual drupal site, cantaloupe
  - services - for karaf, alpaca, crayfish, fedora, cantaloupe, blazegraph, solr
- There are two RDS databases connected as well: for drupal and fedora
- The ideal state might look something like: https://www.lucidchart.com/invitations/accept/8a83a394-5cf6-48c8-9434-6803456c283a
- For the time being, I've set up separate security groups for each EC2 instance to allow inbound traffic on the required ports from various locations (such as ASU IPs and the other EC2 instances)
- All EC2 instances have static Elastic Block volumes associated with them (8GB each)
- The webserver also has a related S3 bucket (asulibdev-islandora-bucket) which is currently being used for islandora_bagger to send preservation bags. It has a automatic rule to push to Glacier after 30 days of inactivity.
- An RDS MYSQL instance has also been provisioned and connection is allowed to the webserver for the purpose of hosting the drupal database. In the future, additional RDS instances can be created for the gemini database, matomo database. (The Riprap database is currently being integrated with the Drupal database). You can connect to the RDS instance from the webserver EC2 instance manually like `mysql -u drupal8 -p -h islandora-drupal.cvznsvixsvec.us-west-2.rds.amazonaws.com --port 3306`

# Updating existing components
## Islandora modules
`composer update drupal/module_name`

# Component Glossary and Notes
(in alphabetical order)

## [Alpaca](https://github.com/Islandora-CLAW/Alpaca)
Apache Camel middleware which listens to events emitted from Drupal and distributes them to the microservices. ASU fork is [here](https://github.com/asulibraries/alpaca).

## Api-X
https://github.com/fcrepo4-labs/fcrepo-api-x/blob/master/src/site/markdown/apix-design-overview.md

## [Blazegraph](https://www.blazegraph.com/)
A high performance graph database, aka the triplestore


## [Carapace](https://github.com/Islandora-CLAW/carapace)
A Drupal theme for Islandora, based on [AdaptiveTheme](https://www.drupal.org/project/adaptivetheme)

## [Cantaloupe](https://github.com/cantaloupe-project/cantaloupe)
A IIIF compliant image server, written in Java

## [Chullo](https://github.com/Islandora-CLAW/chullo)
A PHP client which directly communicates with the Fedora 5 API. ASU fork is [here](https://github.com/asulibraries/chullo)

## [ClamAV](https://www.clamav.net/)
A virus scanning application.
If you get an error in the Drupal Status report saying that it couldn't connect to ClamAV, likely the service isn't running.
1. SSH to the VM `vagrant ssh`
2. `sudo service clamav-freshclam status`
3. If its down, restart it `sudo service clamav-freshclam restart` or if its up, proceed to the next step. Note that sometimes it needs to be up for 1 minute before proceeding to the next step.
4. `sudo service clamav-daemon status` Likely this will tell you it is down. If freshclam is running, it needs to get the updated ClamAV Virus Database (.cvd) file(s) from freshclam before the daemon can be started.
5. `sudo service clamav-daemon restart`

## [Crayfish](https://github.com/Islandora-CLAW/Crayfish)
A collection of micro-services: Gemini, Homarus, Houdini, Hypercube, Milliner, and Recast. ASU fork is [here](https://github.com/asulibraries/Crayfish)

## [Controlled Access Terms](https://github.com/Islandora/controlled_access_terms)
An Islandora module that adds vocabularies and fields to allowed controlled vocabulary usage in Islandora. The most significant of these being the "linked agent" field with a custom "Typed Relation" field type.

## [Crayfish-commons](https://github.com/Islandora/Crayfish-Commons)
Shared code for the Crayfish microservices

## Fedora

## Fits

## Flysystem
- https://github.com/Islandora-CLAW/CLAW/blob/master/docs/technical-documentation/flysystem.md
- Component which allows persistance of binary files in Drupal to actually occur in fedora

## Gemini
https://github.com/Islandora-CLAW/Crayfish/tree/master/Gemini
- Mapping service from Drupal UUID to Fedora URI
enable JWT Authentication Issuer module
To use any of the API endpoints or Gemini, you need a JWT token - which can be generated with a request like `curl -i -u admin:islandora http://localhost:8000/jwt/token`.
ie. ```curl -X GET \
  http://localhost:8000/gemini/4c82e5c1-73bb-402d-ab3c-e6e1d49fa9f9 \
  -H 'Authorization: Bearer tokenhere' '```

## Homarus
## Houdini
## Hypercube

## Karaf
log file is in /opt/karaf/data/log


## OpenSeaDragon
IIIF-compliant image viewer
https://github.com/Islandora-CLAW/openseadragon

## Microservices

## Milliner
## Recast
## Syn
