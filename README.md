# Drupal Backup Tool (DBT)

This console application provides a quick and configurable way to backup Drupal sites via rsync. Built on the [Symfony](http://symfony.com/) framework and extending the [Command Class](http://api.symfony.com/2.0/Symfony/Component/Console/Command/Command.html) it provides a configurable way of backing up larger numbers of Drupal sites with little effort.


## Options

*  -e, --env=ENV            Backup specific environments (multiple values allowed)
*  -w, --site=SITE          Backup specific sites (multiple values allowed)
*  -s, --server=SERVER      Backup from specific servers (multiple values allowed)
*  -d, --dry-run            Shows all Docroots, Servers and Environments that would have been backed up
*  -f, --force              If set, the backup will force a new backup into a uniquely named directory.
*  -b, --backup=BACKUP      Select a combination of code, files and db to download only those components. (multiple values allowed)
*  -p, --password=PASSWORD  The SSH Key password to allow automated backup when run non-interactively.


## Config files

Configuration files are stored in the app/config directory, although this can be overwritten by extending the AbstractDrupalConfigBase class. These configuration files can be loaded to provide not only DBT configuration but also server and environment configuration for backup-able sites.

**Server**

The project comes with example server config to show the type of information required. At the bare minimum, a human readable name, machine name, server hostname, ssh user, ssh port and ssh key should be included.

````
name: Acquia Server
machine: acquia_server
hostname: web-888.prod.hosting.acquia.com
user: adam
port: 22
key: /Users/adam/.ssh/id_rsa
````


**Site**

Site configuration requires the same human and machine name for each configuration file. It also splits sites to cater for different environments on different servers or at alternate path locations. Each environment should be keyed by a machine name (dev, test, prod etc) and have server, path and url elements. The server value is the machine name of a server config.

````
name: My Personal Blog
machine: adammalone_net
environments:
  prod:
    server: "acquia_server"
    path: "/var/www/html/adam.prod/docroot"
    url: "www.adammalone.net"
  test:
    server: "acquia_server"
    path: "/var/www/html/adam.test/docroot"
    url: "test.adammalone.net"
````

**Local**

The local configuration file provides DBT with values should they be needed. An example of this is seen in the getBackupLocation method of the LocalBackupConfig class where the local storage directory is configured.


## Examples



## License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
