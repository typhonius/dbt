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
  // todo private these
  private $servers;
  private $docroots;
  private $envs;
  private $backup_path;
  private $backup_file;

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
          var_dump("server is $server, docroot is $docroot, env is $env");
        }
      }
    }

  }

  private function loadFromConfig($stage) {
    $config = new Config();
    return $config->returnInfoArray($stage, 'machine');
  }

  public function runBackup($options) {
    global $configs;



    foreach ($configs->docroots as $docroots) {
      // opts match machine name do that
      // or all do all
      // or none show opts help
      // wrong opts throw an error
      var_dump($docroots);

      //throw new IncorrectSitenameException();

    }

    $this->docroot = 'adammalone_net';
    $this->env = 'test';
    $this->backup_path = self::generateRootBackupDirName($this->docroot, $this->env);
    //$this->backup_file = self::generateBackupFileName($this->docroot, $this->env);


    //var_dump($this);
    //exec('rsync -aPh hermes:/var/www/html/adammalone/docroot/sites/all/modules/views /tmp');

  }

  private function generateRootBackupDirName($docroot, $env) {
    global $configs;
    $dir = "{$configs->local}/" . $this->getBackupServer($docroot, $env) . "/{$docroot}/{$env}";

    // TODO use File::checkDirectory
    if (!file_exists($dir)) { // TODO include force parameter
      @mkdir($dir, 0755, TRUE);
    }

    return $dir;
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

  private function generateBackupFileName($docroot, $env) {
    return "{$docroot}-{$env}-" . date('Y-m-d');
  }

  private function getBackupServer($docroot, $env) {
    global $configs;
    return $configs->docroots[$docroot]->data['environments'][$env]['server'];
  }

}