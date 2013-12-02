<?php

namespace backup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use utils\DrupalSite;

define('FILEDIR', 'files');
define('CODEDIR', 'code');
define('DBDIR', 'db');

class BackupCommand extends Command {
  private $backup_path;
  private $config;
  private $docroot;
  private $server;
  private $env;
  private $verbosity;
  private $download;

  public function __construct() {
    parent::__construct();
  }

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
      ->addOption('download', 'dl', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Select a combination of code, files and db to download only those components.', array());

  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    global $configs;

    $class = "config\\" . $configs["class"];

    if ($input->getOption('show')) {
      $all = new $class(array(), array(), array());
      $output->writeln('<info>Information about all resources will go here.</info>');
      return;
    }

    $this->config = new $class($input->getOption('servers'), $input->getOption('docroots'), $input->getOption('envs'));
    $this->verbosity = $input->getOption('verbose');
    $this->download = $input->getOption('download');

    foreach ($this->config->envs as $env) {
      $site = new DrupalSite($this->config, $env);
      $this->runBackup($output, $site);
    }
  }

  private function runBackup(OutputInterface $output, DrupalSite $site) {

    // TODO this should be part of config
    //$this->config->
    //$this->generateBackupPath();



    // The user may set defaults in the site yaml file. These may be overwritten
    // using the command line --download option. If neither are set we default
    // to download everything.
    $download = $this->getDownloadOptions($this->download, $site->backup);

    $command = array();

    if (in_array('files', $download)) {
      $public_files = $site->execRemoteCommand('DRUPAL_BOOTSTRAP_VARIABLES',  "print variable_get(\"file_public_path\", \"sites/default/files\");");
      $command[] = escapeshellcmd("rsync -aPh -f '+ */' -f '+ */files/***' -f '- *' {$this->server['sshuser']}:{$this->docroot['environments'][$this->env]['path']}/{$public_files} {$this->backup_path}/" . CODEDIR);
    }
    if (in_array('code', $download)) {
      $command[] = escapeshellcmd("rsync -aPh -f '- sites/*/files' -f '- .git' {$this->server['sshuser']}:{$this->docroot['environments'][$this->env]['path']}/ {$this->backup_path}/" . FILEDIR);
    }
    if (in_array('db', $download)) {
      // Get the DB credentials
      $databases = unserialize($site->execRemoteCommand('DRUPAL_BOOTSTRAP_CONFIGURATION', "global \$databases; print serialize(\$databases);"));
      $credentials = &$databases['default']['default'];
      // TODO add hostname and port for non-local remote MySQL installations. -h -P
      $dump_command = escapeshellcmd("mysqldump '-u{$credentials['username']}' '-p{$credentials['password']}' '{$credentials['database']}'");
      $command[] = "ssh -p{$this->server['port']} {$this->server['user']}@{$this->server['hostname']} '{$dump_command} | gzip -c' > {$this->backup_path}/" . DBDIR . "/{$this->docroot['machine']}.sql.gz";
    }
    foreach ($command as $c) {
      if ($this->verbosity) {
        //$output->writeln(passthru($rsync));
        //passthru($c);
      }
      else {
        //exec($c);
      }
    }
  }

  /**
   * Executes a command remotely after bootstrapping Drupal to the requested level.
   *
   * @param string $bootstrap
   * @param string $command
   * @return string
   */
  private function execRemoteCommand($bootstrap = 'DRUPAL_BOOTSTRAP_FULL', $command = '') {
    $remote_command = "php -r '\$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->docroot['environments'][$this->env]['uri']}\"; define(\"DRUPAL_ROOT\", \"{$this->docroot['environments'][$this->env]['path']}\"); require_once DRUPAL_ROOT . \"/includes/bootstrap.inc\"; drupal_bootstrap({$bootstrap}); {$command};'";
    $connection = ssh2_connect($this->server['hostname'], $this->server['port']);
    ssh2_auth_pubkey_file($connection, $this->server['user'], $this->server['key'] . '.pub', $this->server['key']);
    $stream = ssh2_exec($connection, $remote_command);
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    return stream_get_contents($stream_out);
  }

  private function generateBackupPath() {
    $backup = $this->config->getBackupLocation();
    $dir = "{$backup}/{$this->docroot['machine']}/{$this->env}/{$this->server['machine']}";
    if (File::checkDirectory($dir)) {
      $this->backup_path = $dir;
      $this->generateBackupDirs();
    }
    else {
     // throw new IncorrectSitenameException
      // error
    }
  }

  private function getDownloadOptions($cli, $conf) {
    $downloads = isset($conf['download']) ? $conf['download'] : array();
    $downloads = !empty($cli) ? $cli : $downloads;
    $downloads = empty($downloads) ? array('files', 'code', 'db') : $downloads;

    return $downloads;
  }

  private function generateBackupDirs() {
    if (file_exists($this->backup_path)) {
      @mkdir("{$this->backup_path}/" . CODEDIR, 0755, TRUE);
      @mkdir("{$this->backup_path}/" . FILEDIR, 0755, TRUE);
      @mkdir("{$this->backup_path}/" . DBDIR, 0755, TRUE);
    }
  }

}
