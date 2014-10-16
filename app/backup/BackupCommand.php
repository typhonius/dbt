<?php

namespace BackupOop\Backup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BackupOop\Utils\DrupalSite;
use BackupOop\Config\ConfigInterface;

class BackupCommand extends Command {
  private $verbosity;
  private $backup;

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName("Backup")
      ->setHelp("Help will go here") // TODO Maybe use a function to return help.
      ->setDescription('Backup all available docroots on all available servers.')
      ->addOption('envs', 'e', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup specific environments', array())
      ->addOption('docroots', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup specific docroots', array())
      ->addOption('servers', 's', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup from specific servers', array())
      ->addOption('show', null, InputOption::VALUE_NONE, 'Shows all Docroots, Servers and Environments available')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, the backup will force a new backup.')
      ->addOption('backup', 'b', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Select a combination of code, files and db to download only those components.', array());

  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    global $configs;

    /* @var $class ConfigInterface */
    $class = $configs["class"];

    if ($input->getOption('show')) {
      $all = new $class(array(), array(), array());
      $output->writeln('<info>Information about all resources will go here.</info>');
      return;
    }

    /** @var ConfigInterface $config */
    $config = new $class($input->getOption('servers'), $input->getOption('docroots'), $input->getOption('envs'));
    $this->verbosity = $input->getOption('verbose');
    $this->backup = $input->getOption('backup');

    $this->runBackup($output, $config);

    $config->runPostBackupTasks();
  }

  /**
   * @param OutputInterface $output
   * @param ConfigInterface $config
   */
  private function runBackup(OutputInterface $output, ConfigInterface $config) {

    /** @var DrupalSite $site */
    foreach ($config->getSites() as $site) {

      // The user may set defaults in the site yaml file. These may be overwritten
      // using the command line --download option. If neither are set we default
      // to download everything.
      $download = $this->getDownloadOptions($this->backup, $site);

      $command = [];
      // get conf_path()

      if (in_array('files', $download)) {
        $backup_path = $config->getBackupLocation($site, 'files');
        $public_files = $site->execRemoteCommand('DRUPAL_BOOTSTRAP_VARIABLES', "print variable_get(\"file_public_path\", \"sites/default/files\");");
        // @TODO have external function to generate this command
        $command[] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPh -f '+ */' -f '+ */files/***' -f '- *' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/{$public_files} {$backup_path}");
      }

      if (in_array('code', $download)) {
        $backup_path = $config->getBackupLocation($site, 'code');
        $command[] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPh -f '- sites/*/files' -f '- .git' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/ {$backup_path}");
      }
      if (in_array('db', $download)) {
        $backup_path = $config->getBackupLocation($site, 'db');
        // Get the DB credentials
        $databases = unserialize($site->execRemoteCommand('DRUPAL_BOOTSTRAP_CONFIGURATION', "global \$databases; print serialize(\$databases);"));
        $credentials = &$databases['default']['default'];

        if ($credentials['driver'] == 'mysql') {
          $credentials['port'] = $credentials['port'] ?: 3306;
          $dump_command = escapeshellcmd("mysqldump '-h{$credentials['host']}' '-P{$credentials['port']}' '-u{$credentials['username']}' '-p{$credentials['password']}' '{$credentials['database']}'");
          $command[] = "ssh -p{$site->getPort()} {$site->getUser()}@{$site->getHostname()} '{$dump_command} | gzip -c' > {$backup_path}/{$site->getDocroot()}.sql.gz";
        }
        else {
          //throw new \Exception;
        }
      }
      foreach ($command as $c) {
//        if ($this->verbosity) {
//          $output->writeln(passthru($c));
          passthru($c);
//        }
//        else {
//          exec($c);
//        }
      }
    }
  }

  /**
   * @param array $cli
   * @param DrupalSite $conf
   * @return array
   */
  private function getDownloadOptions($cli, DrupalSite $conf) {
    $downloads = !empty($cli) ? $cli : $conf->getBackup();

    $allowed = ['db', 'code', 'files'];
    return array_intersect(array_combine($downloads, $downloads), $allowed);
  }

}
