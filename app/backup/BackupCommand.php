<?php
/**
 * Created by JetBrains PhpStorm.
 * User: adam.malone
 * Date: 20/9/13
 * Time: 12:02 59
 * To change this template use File | Settings | File Templates.
 */

namespace backup;

use utils\IncorrectSitenameException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

define('FILEDIR', 'files');
define('CODEDIR', 'code');

class BackupCommand extends Command {
  private $servers;
  private $docroots;
  private $envs;
  private $backup_path;
  private $backup_file;
  private $config;
  private $docroot;
  private $server;
  private $env;

  public function __construct() {
    parent::__construct();
    $this->config = new Config();
  }

  protected function configure() {
    $this
      ->setName("Backup")
      ->setHelp("Help will go here")
      ->setDescription('Backup all available docroots on all available servers.')
      //->addArgument('docroots', InputArgument::OPTIONAL, 'Which docroots should be backed up?')
      ->addOption('envs', 'e', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup specific environments', array('all'))
      ->addOption('docroots', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup specific docroots over all servers', array('all'))
      ->addOption('servers', 's', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup all docroots on a particular server', array('all'))
      ->addOption('show', null, InputOption::VALUE_NONE, 'Shows Docroots, Servers and Environments available')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, the backup will force a new backup.');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($input->getOption('show')) {
      $output->writeln('<info>foo</info>');
      return;
    }

    $this->servers = $input->getOption('servers');
    $this->docroots = $input->getOption('docroots');
    $this->envs = $input->getOption('envs');

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
          // TODO have to actually make sure the docroot and env exist on the server.
          if ($this->config->isValidConfig($docroot, $server, $env)) {
            $this->docroot = $docroot;
            $this->server = $server;
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
    $this->backup_path = $this->generateBackupPath();
    //$output->writeln("<info>Generating backup paths</info>");
    $this->generateBackupDirs();
    // Ensure libssh2 and PHP ssh2 are installed
    if (function_exists('ssh2_connect')) {
      $connection = ssh2_connect($this->server);
      //ssh2_connect('shell.example.com', 22, array('hostkey'=>'ssh-rsa'));
//      if (ssh2_auth_pubkey_file($connection, 'username',
//        '/home/username/.ssh/id_rsa.pub',
//        '/home/username/.ssh/id_rsa', 'secret')) {
//        echo "Public Key Authentication Successful\n";
//      } else {
//        die('Public Key Authentication Failed');
//      }
    }
    else {
      // Otherwise fall back to the good old shell escape
    }


  }

  private function generateBackupPath() {
    global $configs;
    $dir = "{$configs->local}/$this->server/{$this->docroot}/{$this->env}";



    // TODO use File::checkDirectory
    if (!file_exists($dir)) { // TODO include force parameter
      @mkdir($dir, 0755, TRUE);
    }

    return $dir;
  }

  private function generateBackupDirs() {
    if (file_exists($this->backup_path)) {
      @mkdir("{$this->backup_path}/" . CODEDIR, 0755, TRUE);
      @mkdir("{$this->backup_path}/" . FILEDIR, 0755, TRUE);
    }
  }

  private function generateFilesBackupDirName($docroot, $env) {
    $dir = self::generateRootBackupDirName($docroot, $env) . "/" . FILEDIR;

    if (!file_exists($dir)) {
      @mkdir($dir, 0755, TRUE);
    }

    return $dir;
  }

  private function generateCodeBackupDirName($docroot, $env) {
    $dir = self::generateRootBackupDirName($docroot, $env) . "/" . CODEDIR;

    if (!file_exists($dir)) {
      @mkdir($dir, 0755, TRUE);
    }

    return $dir;
  }

  private function generateBackupFileName() {
    return "{$this->docroot}-{$this->env}-" . date('Y-m-d');
  }

  private function getBackupServer($docroot, $env) {
    global $configs;
    return $configs->docroots[$docroot]->data['environments'][$env]['server'];
  }

}