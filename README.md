backup_oop
==========

This script provides a quick and configurable way to backup Drupal sites via rsync. Built on the [Symfony](http://symfony.com/) framework and extending the [Command Class](http://api.symfony.com/2.0/Symfony/Component/Console/Command/Command.html) it provides a configurable way of backing up larger numbers of Drupal sites with little effort.


Options
-------
* --envs (-e)           Backup specific environments (default: ["all"]) (multiple values allowed)
* --docroots (-d)       Backup specific docroots (default: ["all"]) (multiple values allowed)
* --servers (-s)        Backup from specific servers (default: ["all"]) (multiple values allowed)
* --show                Shows Docroots, Servers and Environments available
* --force (-f)          If set, the backup will force a new backup.
* --code                Specify this option to only download the code from a remote site.
* --files               This option will only rsync the files from a site.
 
