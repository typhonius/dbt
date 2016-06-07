Drupal Backup Tool (DBT)
==========

This console application provides a quick and configurable way to backup Drupal sites via rsync. Built on the [Symfony](http://symfony.com/) framework and extending the [Command Class](http://api.symfony.com/2.0/Symfony/Component/Console/Command/Command.html) it provides a configurable way of backing up larger numbers of Drupal sites with little effort.


Options
-------
* --envs (-e)           Backup specific environments (defaults to all) (multiple values allowed)
* --docroots (-d)       Backup specific docroots (defaults to all) (multiple values allowed)
* --servers (-s)        Backup from specific servers (defaults to all) (multiple values allowed)
* --show                Shows Docroots, Servers and Environments available
* --force (-f)          If set, the backup will force a new backup.
* --backup (-b)         Select a combination of code, files and db to download only those components. (multiple values allowed)

Config files
------------
**Server**
The project comes with example server config to show the type of information required. At the bare minimum, include a human readable name, machine name, server hostname, ssh user, ssh port and ssh key.

````
name: Example Server
machine: example_server
hostname: foo.example.com
user: ex
port: 15671
key: /Users/example/.ssh/id_rsa
````


**Site**
Site configuration requires the same human and machine name for each configuration file. It also splits sites into environments to cater for docroots on different servers or locations. Each environment should be keyed by a machine name (dev, test, prod etc) and have server, path and url elements. The server value is the machine name of a server config.

````
name: My Example Site
machine: example_docroot
environments:
  prod:
    server: "example_server"
    path: "/var/www/html/example_docroot/docroot"
    url: "example.com"
  test:
    server: "other_example"
    path: "/var/www/html/example_docroot/docroot"
    url: "dev.example.com"
````


Examples
--------

