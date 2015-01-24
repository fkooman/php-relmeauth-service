# Introduction
This is a RelMeAuth implementation written in PHP.

# Installation
RPM packages are available for Fedora and CentOS/RHEL:

    $ sudo yum -y install yum-plugin-copr
    $ yum copr enable -y fkooman/php-base
    $ yum copr enable -y fkooman/php-relmeauth
    $ yum install -y php-relmeauth-service

Restart Apache:

    $ sudo service httpd restart

# Configuration
Initialize the database, by default this is SQLite. If you want to use any 
other database please first modify the configuration file
`/etc/php-relmeauth-service/config.ini`.

    $ sudo -u apache php-relmeauth-service-init

Also make sure you configure the details for the providers you want to use 
with php-relmeauth-service in `/etc/php-relmeauth-service/config.ini`.

# License
Licensed under the GNU Affero General Public License as published by the Free 
Software Foundation, either version 3 of the License, or (at your option) any 
later version.

    https://www.gnu.org/licenses/agpl.html

This roughly means that if you use this software in your service you need to 
make the source code available to the users of your service (if you modify
it). Refer to the license for the exact details.
