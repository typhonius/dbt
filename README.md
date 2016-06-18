# Drupal Backup Tool (DBT)

This console application provides a quick and configurable way to backup Drupal sites via rsync. Built on the [Symfony](http://symfony.com/) framework and extending the [Command Class](http://api.symfony.com/2.0/Symfony/Component/Console/Command/Command.html) it provides a configurable way of backing up larger numbers of Drupal sites with little effort.


## Options

*  -e, --env=ENV                  Backup specific environments (multiple values allowed)
*  -w, --site=SITE                Backup specific sites (multiple values allowed)
*  -s, --server=SERVER            Backup from specific servers (multiple values allowed)
*  -i, --pipe                     Shows the commands required to run the backup
*  -l, --list                     Lists the available sites, servers, and environments to backup
*  -f, --force                    If set, the backup will force a new backup into a uniquely named directory
*  -b, --backup=BACKUP            Select a combination of code, files and db to download only those components (multiple values allowed)
*  -p, --password=PASSWORD        The SSH Key password to allow automated backup when run non-interactively
*  -d, --destination=DESTINATION  Manually select where to the site backup destination
*  -r, --dry-run                  Shows all Docroots, Servers and Environments that would have been backed up

## Config files

Configuration files are stored in the app/config directory, although this can be overwritten by extending the AbstractDrupalConfigBase class. These configuration files can be loaded to provide not only DBT configuration but also server and environment configuration for backup-able sites.

**Server**

Stored in app/config/servers directory; server configuration should contain, at the bare minimum, a human readable name, machine name, server hostname, ssh user, ssh port and ssh key.

````
name: Acquia Server
machine: acquia_server
hostname: web-888.prod.hosting.acquia.com
user: adam
port: 22
key: /Users/adam/.ssh/id_rsa
````

````
name: Example Server
machine: example_server
hostname: foo.example.com
user: ex
port: 15671
key: /Users/example/.ssh/id_rsa
````


**Site**

Site configuration is stored at app/config/sites and requires the same human and machine name for each configuration file. It also splits sites to cater for different environments on different servers or at alternate path locations. Each environment should be keyed by a machine name (dev, test, prod etc) and have server, path and url elements. The server value is the machine name of a server config.

````
name: My Personal Blog
machine: adammalone_net
version: 7
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

````
name: "My Example Site"
machine: "example_docroot"
version: 8
environments:
  prod:
    server: "example_server"
    path: "/var/www/html/example_docroot/docroot"
    url: "example.com"
````

**Local**

The local configuration file provides DBT with values should they be needed. An example of this is seen in the getBackupLocation method of the LocalBackupConfig class where the local storage directory is configured.


## Examples

````
# Show all possible servers, sites and environments that can be backed up.
./bin/dbt.php dbt:backup --list
  
# Backup all production websites from all servers.
./bin/dbt.php dbt:backup --env prod
  
# Backup the production databases of both 'adammalone_net' and 'example_docroot' websites.
./bin/dbt.php dbt:backup --site adammalone_net --site example_docroot --env prod --backup db
  
# Backup any production website hosted on the 'acquia_server' server.
./bin/dbt.php dbt:backup --server acquia_server --env prod
  
# Create a brand new backup of the production environment from the 'adammalone_net' site using an SSH key password stored in a file.
./bin/dbt.php dbt:backup --env prod --site adammalone_net --force --password "$(< /tmp/keypass)"

````

## License

DBT is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
