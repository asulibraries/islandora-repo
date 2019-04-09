ASU Digital Repository on Islandora8
===================================
This repository is a drupal root for ASU Digital Repository built using Islandora8.

For development purposes, this repository should be integrated with the (islandora provided vagrant environment)[https://github.com/Islandora-Devops/claw-playbook].

It will also include ansible scripts for provisioning and deploying to additional environments.

Local Development Setup
=======================
1. Go to the claw-playbook repo
2. Clone claw-playbook
3. cd into claw-playbook
4. Clone this repo into a folder called claw-sandbox
3. Run vagrant up (from within the claw-playbook root)


If you get an error in the Drupal Status report saying that it couldn't connect to ClamAV, likely the service isn't running.
1. SSH to the VM `vagrant ssh`
2. `sudo service clamav-freshclam status`
3. If its down, restart it `sudo service clamav-freshclam restart` or if its up, proceed to the next step. Note that sometimes it needs to be up for 1 minute before proceeding to the next step.
4. `sudo service clamav-daemon status` Likely this will tell you it is down. If freshclam is running, it needs to get the updated ClamAV Virus Database (.cvd) file(s) from freshclam before the daemon can be started.
5. `sudo service clamav-daemon restart`
