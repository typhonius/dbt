<?php

namespace backup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

define('FILEDIR', 'files');
define('CODEDIR', 'code');
define('DBDIR', 'db');

class BackupCommand extends Command {
  private $servers;
  private $docroots;
  private $envs;
  private $backup_path;
  private $config;
  private $docroot;
  private $server;
  private $env;
  private $verbosity;
  private $download;

  public function __construct() {
    global $configs;
    parent::__construct();

    $class = "config\\" . $configs["class"];
    $this->config = new $class;
  }

  protected function configure() {
    $this
      ->setName("Backup")
      ->setHelp("Help will go here")
      ->setDescription('Backup all available docroots on all available servers.')
      ->addOption('envs', 'e', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup specific environments', array('all'))
      ->addOption('docroots', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup specific docroots', array('all'))
      ->addOption('servers', 's', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup from specific servers', array('all'))
      ->addOption('show', null, InputOption::VALUE_NONE, 'Shows Docroots, Servers and Environments available')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, the backup will force a new backup.')
      ->addOption('download', 'dl', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Select a combination of code, files and db to download only those components.', array());

  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($input->getOption('show')) {
      $output->writeln('<info>foo</info>');
      return;
    }

    $this->servers = $input->getOption('servers');
    $this->docroots = $input->getOption('docroots');
    $this->envs = $input->getOption('envs');
    $this->verbosity = $input->getOption('verbose');
    $this->download = $input->getOption('download');

    if ($this->servers[0] == 'all') {
      $this->servers = $this->loadFromConfig('servers');
    }
    if ($this->docroots[0] == 'all') {
      $this->docroots = $this->loadFromConfig('docroots');
    }
    if ($this->envs[0] == 'all') {
      // TODO parameterise this
      $this->envs = array('local', 'dev', 'test', 'stage', 'prod');
    }

    // Nesty nest
    foreach ($this->servers as $server) {
      foreach ($this->docroots as $docroot) {
        foreach ($this->envs as $env) {
          if ($this->config->isValidConfig($docroot, $server, $env)) {
            $this->docroot = $this->config->getDocrootConfig($docroot);
            $this->server = $this->config->getServerConfig($server);
            $this->env = $env;
            $output->writeln("<info>Running backup of $docroot, $env from $server</info>");
            $this->runBackup($output);
          }
        }
      }
    }

  }

  private function loadFromConfig($stage) {
    return $this->config->returnInfoArray($stage, 'machine');
  }

  private function runBackup(OutputInterface $output) {
    $this->generateBackupPath();

    $command = array();

    // The user may set defaults in the site yaml file. These may be overwritten
    // using the command line --download option. If neither are set we default
    // to download everything.
    $downloads = isset($this->docroot['environments'][$this->env]['download']) ? $this->docroot['environments'][$this->env]['download'] : array();

    $downloads = !empty($this->download) ? $this->download : $downloads;

    $downloads = empty($downloads) ? array('files', 'code', 'db') : $downloads;

    // TODO put in host, port etc also instantiate $this server with port 22 as default and localhost as default host.
    if (in_array('files', $downloads)) {
      $public_files = $this->execRemoteCommand('DRUPAL_BOOTSTRAP_VARIABLES',  "print variable_get(\"file_public_path\", \"sites/default/files\");");
      $command[] = escapeshellcmd("rsync -aPh -f '+ */' -f '+ */files/***' -f '- *' {$this->server['sshuser']}:{$this->docroot['environments'][$this->env]['path']}/{$public_files} {$this->backup_path}/" . CODEDIR);
    }
    if (in_array('code', $downloads)) {
      $command[] = escapeshellcmd("rsync -aPh -f '- sites/*/files' -f '- .git' {$this->server['sshuser']}:{$this->docroot['environments'][$this->env]['path']}/ {$this->backup_path}/" . FILEDIR);
    }
    if (in_array('db', $downloads)) {
      // Get the DB credentials
      $databases = unserialize($this->execRemoteCommand('DRUPAL_BOOTSTRAP_CONFIGURATION', "global \$databases; print serialize(\$databases);"));
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

  private function generateBackupDirs() {
    if (file_exists($this->backup_path)) {
      @mkdir("{$this->backup_path}/" . CODEDIR, 0755, TRUE);
      @mkdir("{$this->backup_path}/" . FILEDIR, 0755, TRUE);
      @mkdir("{$this->backup_path}/" . DBDIR, 0755, TRUE);
    }
  }

}
