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
      ->addOption('download', 'dl', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Select a combination of code, files and db to download only those components.', array('all'));

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
    //var_dump($this->download);

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
    // TODO put in host, port etc also instantiate $this server with port 22 as default and localhost as default host.
    if (!$this->download || in_array('files', $this->download)) {
      $command[] = "rsync -aPh -f '- sites/*/files' -f '- .git' {$this->server['sshuser']}:{$this->docroot['environments'][$this->env]['path']} {$this->backup_path}/" . CODEDIR;
    }
    if (!$this->download || in_array('code', $this->download)) {
      // Exclude the most common directories from the rsync to ensure we're as close as possible to just sites/*/files
      $command[] = "rsync -aPh -f '- all' -f '- */modules' -f '- */themes' -f '- */libraries' -f '+ */' -f '+ */files/***' -f '- *' {$this->server['sshuser']}:{$this->docroot['environments'][$this->env]['path']}/sites {$this->backup_path}/" . FILEDIR;
    }
    if (!$this->download || in_array('db', $this->download)) {
      // Get the DB credentials
      $exec = "php -r '\$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->docroot['environments'][$this->env]['uri']}\"; define(\"DRUPAL_ROOT\", \"{$this->docroot['environments'][$this->env]['path']}\"); require_once DRUPAL_ROOT . \"/includes/bootstrap.inc\"; drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION); global \$databases; print serialize(\$databases);'";

      $connection = ssh2_connect($this->server['hostname'], $this->server['port']);
      ssh2_auth_pubkey_file($connection, $this->server['user'], $this->server['key'] . '.pub', $this->server['key']);

      $stream = ssh2_exec($connection, $exec);
      stream_set_blocking($stream, true);
      $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
      $database = unserialize(stream_get_contents($stream_out));

      $credentials = &$database['default']['default'];
      // TODO add hostname and port for non-local remote MySQL installations.
      $dump_command = escapeshellcmd("mysqldump '-u{$credentials['username']}' '-p{$credentials['password']}' '{$credentials['database']}'");
      var_dump($dump_command);
      $command[] = "ssh -p{$this->server['port']} {$this->server['user']}@{$this->server['hostname']} '{$dump_command} | gzip -c' > {$this->backup_path}/" . DBDIR . "/{$this->docroot['machine']}.sql.gz";
    }
    foreach ($command as $c) {
      if ($this->verbosity) {
        //$output->writeln(passthru($rsync));
        //passthru($c);
      }
      else {
        //var_dump($c);
        exec($c);
      }
    }

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
