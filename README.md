# ASU Digital Repository on Islandora8
This repository is a drupal root for ASU Digital Repository built using Islandora8.

For development purposes, this repository should be integrated with the (islandora provided vagrant environment)[https://github.com/Islandora-Devops/claw-playbook].

It will also include ansible scripts for provisioning and deploying to additional environments.

# Local Development Setup
0. Install dependencies
    a. VirtualBox version 5.whatever (not 6.0)
    b. Vagrant (tested up to version 2.1.2)
    c. git
    d. ansible
    e. vagrant vbguest plugin (`vagrant plugin install vagrant-vbguest`)
1. Go to the (ASU claw-playbook repo)[https://github.com/asulibraries/claw-playbook]
2. Clone ASU claw-playbook
3. cd into claw-playbook
4. Clone this repo into a folder called claw-sandbox
5. Run vagrant up (from within the claw-playbook root)

# Ansible
If you've already provisioned your vagrant environment and need to re-run the ASU specific provisioning, you can do so with `ansible-playbook asu-install.yml -i inventory/vagrant -l all -e ansible_ssh_user=$vagrantUser -e islandora_distro=ubuntu/xenial64` Your $vagrantUser will either be ubuntu or vagrant. Check to see what user you become when you `vagrant ssh`.


# Helpful Hints
If you need to update your ansible roles (to get updated versions of the packages), you mine as well `rm -rf roles/external` and `vagrant provision` to fix that. This will take some time.

Understanding how drupal entities relate to fedora objects - https://drive.google.com/file/d/1Ra64mFAsHkPtAf-2BWjdYKDv1Fc2uJSU/view

Get the json-ld for an object in Drupal like so : http://localhost:8000/node/1?_format=jsonld

## So you want to add a module
1. Add the module to the composer requirements in the ASU specific ansible role
2. Add the module to the drush enabling in the ASU specific ansible role
3. Run the ASU specific ansible role

## So you want to update an existing Islandora/Drupal Site
1. pull down the most recent claw-playbook code from github
2. pull down the most recent isladora-repo code from github (in claw-sandbox folder)
3. examine your config sync and see if you have things you either want to export and commit or don't care if you lose them - http://localhost:8000/admin/config/development/configuration
4. vagrant ssh and cd to /var/www/html/drupal, run composer install
5. cd /var/www/html/drupal/web, run drush config:import (note that if your step 3 showed that you have config changes in your DB that aren't in code, those would get wiped away by a config:import)
6. drush udpatedb - to update the database
7. drush cache-rebuild - to clear the cache



# Component Glossary and Notes
(in alphabetical order)

## Alpaca
Apache Camel middleware which listens to events emitted from Drupal and distributes them to the microservices

## Api-X
https://github.com/fcrepo4-labs/fcrepo-api-x/blob/master/src/site/markdown/apix-design-overview.md

## Blazegraph
## Carapace
## Cantaloupe
## Chullo

## ClamAV
A virus scanning application.
If you get an error in the Drupal Status report saying that it couldn't connect to ClamAV, likely the service isn't running.
1. SSH to the VM `vagrant ssh`
2. `sudo service clamav-freshclam status`
3. If its down, restart it `sudo service clamav-freshclam restart` or if its up, proceed to the next step. Note that sometimes it needs to be up for 1 minute before proceeding to the next step.
4. `sudo service clamav-daemon status` Likely this will tell you it is down. If freshclam is running, it needs to get the updated ClamAV Virus Database (.cvd) file(s) from freshclam before the daemon can be started.
5. `sudo service clamav-daemon restart`

## Crayfish
A collection of micro-services.

## Controlled Access Terms

## Crayfish-commons

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
