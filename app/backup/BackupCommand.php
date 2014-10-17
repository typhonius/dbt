<?php

namespace BackupOop\Backup;

use BackupOop\Utils\DatabaseDriverNotSupportedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BackupOop\Utils\DrupalSite;
use BackupOop\Config\ConfigInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class BackupCommand extends Command {
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
    $this->backup = $input->getOption('backup');

    $this->runBackup($input, $output, $config);

    $config->runPostBackupTasks();
  }

  private function runBackup(InputInterface $input, OutputInterface $output, ConfigInterface $config) {

    $allsites = $config->getSites();

    /** @var DrupalSite $site */
    foreach ($allsites as &$site) {
      $output->writeln("<info>Starting backup of {$site->getDocroot()}.{$site->getEnvironment()}</info>");

      if ($input->isInteractive()) {
        if (!$site->isKeypassEntered()) {
          $helper = $this->getHelper('question');
          $question = new ConfirmationQuestion("<question>Is the SSH key for {$site->getHostname()} encrypted?</question> ", FALSE);
          if ($helper->ask($input, $output, $question)) {
            $question = new Question("<question>What is the SSH key password?</question> ");
            $question->setHidden(TRUE);
            $question->setHiddenFallback(FALSE);
            $password = $helper->ask($input, $output, $question);
            $site->setKeypass($password);
          }
        }
      }

      // The user may set defaults in the site yaml file. These may be overwritten
      // using the command line --backup option. If neither are set we default
      // to download everything.
      $download = $this->getDownloadOptions($this->backup, $site);

      $command = [];
      // @TODO get conf_path()
      // include sites all


      if (in_array('files', $download)) {
        $backup_path = $config->getBackupLocation($site, 'files');
        $public_files = $site->execRemoteCommand('DRUPAL_BOOTSTRAP_VARIABLES', "print variable_get(\"file_public_path\", \"sites/default/files\");");
        // @TODO have external function to generate this command
        $command['files']['command'] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPh -f '+ */' -f '+ */files/***' -f '- *' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/{$public_files} {$backup_path}");
        $command['files']['backup_location'] = $backup_path;
      }

      if (in_array('code', $download)) {
//        $output->writeln('<comment>Downloading code</comment>');
        $backup_path = $config->getBackupLocation($site, 'code');
        $command['code']['command'] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPh -f '- sites/*/files' -f '- .git' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/ {$backup_path}");
        $command['code']['backup_location'] = $backup_path;
      }
      if (in_array('db', $download)) {
//        $output->writeln('<comment>Downloading database</comment>');
        $backup_path = $config->getBackupLocation($site, 'db');
        // Get the DB credentials
        $databases = unserialize($site->execRemoteCommand('DRUPAL_BOOTSTRAP_CONFIGURATION', "global \$databases; print serialize(\$databases);"));
        $credentials = &$databases['default']['default'];

        if ($credentials['driver'] == 'mysql') {
          $credentials['port'] = $credentials['port'] ?: 3306;
          $dump_command = escapeshellcmd("mysqldump '-h{$credentials['host']}' '-P{$credentials['port']}' '-u{$credentials['username']}' '-p{$credentials['password']}' '{$credentials['database']}'");
          $command['db']['command'] = "ssh -p{$site->getPort()} {$site->getUser()}@{$site->getHostname()} '{$dump_command} | gzip -c' > {$backup_path}/{$site->getDocroot()}.sql.gz";
          $command['db']['backup_location'] = $backup_path;
        }
        else {
          throw new DatabaseDriverNotSupportedException(sprintf("The remote database driver is %s. Only MySQL is accepted.", $credentials['driver']));
        }
      }
      foreach ($command as $stage => $c) {
        // try catch here to get as much even if one fails
        $output->writeln(sprintf("<comment>Downloading %s</comment>", $stage));
        if ($output->isVerbose()) {
          passthru($c['command']);
        }
        else {
          exec($c['command']);
        }
        $output->writeln(sprintf("<info>%s downloaded to %s</info>", $stage, $c['backup_location']));
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
