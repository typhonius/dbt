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
  private $verbosity;
  private $download;

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
      ->addOption('docroots', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup specific docroots', array('all'))
      ->addOption('servers', 's', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Backup from specific servers', array('all'))
      ->addOption('show', null, InputOption::VALUE_NONE, 'Shows Docroots, Servers and Environments available')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, the backup will force a new backup.')
      ->addOption('code', null, InputOption::VALUE_NONE, 'Backup only downloads the code not any media files.')
      ->addOption('files', null, InputOption::VALUE_NONE, 'Backup will only download media uploaded by the user to the site, not code.');

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
    $this->download = !($input->getOption('code') ^ $input->getOption('files')) ? 'both' : ($input->getOption('code') ? 'code' : 'files');

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
    $this->backup_path = $this->generateBackupPath();
    $this->generateBackupDirs();
    if ($this->download != 'files') {
      $rsync = "rsync -aPh -f '- sites/*/files' -f '- .git' {$this->server['sshuser']}:{$this->docroot['environments'][$this->env]['path']} {$this->backup_path}/" . CODEDIR;
      // TODO create flag to compress the backup to a tar.gz archive
      if ($this->verbosity) {
        //$output->writeln(passthru($rsync));
        passthru($rsync);
      }
      else {
        exec($rsync);
      }
    }
    if ($this->download != 'code') {
      // Exclude the most common directories from the rsync to ensure we're as close as possible to just sites/*/files
      $rsync = "rsync -aPh -f '- all' -f '- */modules' -f '- */themes' -f '- */libraries' -f '+ */' -f '+ */files/***' -f '- *' {$this->server['sshuser']}:{$this->docroot['environments'][$this->env]['path']}/sites {$this->backup_path}/" . FILEDIR;
      // TODO create flag to compress the backup to a tar.gz archive
      // TODO add in config to just do code OR files
      if ($this->verbosity) {
        //$output->writeln(passthru($rsync));
        passthru($rsync);
      }
      else {
        exec($rsync);
      }
    }

  }

  private function generateBackupPath() {
    global $configs;
    $dir = "{$configs->local}/{$this->docroot['machine']}/{$this->env}/{$this->server['machine']}";

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