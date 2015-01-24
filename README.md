# Introduction
This is a RelMeAuth implementation written in PHP.

# Installation
RPM packages are (almost) available for Fedora and CentOS/RHEL:

    $ sudo yum -y install yum-plugin-copr
    $ sudo yum copr enable fkooman/php-relmeauth
    $ sudo yum -y install php-relmeauth-service

Restart Apache:

    $ sudo service httpd restart

# Configuration
Initialize the database, by default this is SQLite. If you want to use any 
other database please first modify the configuration file
`/etc/php-relmeauth-service/config.ini`.

    $ sudo -u apache php-relmeauth-service-init

Also make sure you configure the details for the providers you want to use 
with php-relmeauth-service.
