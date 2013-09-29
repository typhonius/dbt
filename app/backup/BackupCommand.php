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

define('FILEDIR', 'files');
define('CODEDIR', 'code');

class BackupCommand extends Command {
  public $docroot;
  public $env;
  public $backup_path;
  public $backup_file;


  protected function configure() {
    $this
      ->setName("Backup")
      ->setHelp("Help will go here")
      ->setDescription('Backup all available docroots on all available servers.')
      ->addArgument('docroot', InputArgument::OPTIONAL, 'Which docroots should be backed up?')
      ->addOption('env', 'e', InputOption::VALUE_OPTIONAL, 'Backup specific environments')
      //->addOption('docroots', 'd', InputArgument::OPTIONAL, 'Backup specific docroots over all servers')
      ->addOption('servers', 's', InputOption::VALUE_OPTIONAL, 'Backup all docroots on a particular server')
      ->addOption('show', null, InputOption::VALUE_NONE, 'Shows Docroots, Servers and Environments available')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, the backup will force a new backup.');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $servers = $input->getOption('servers');
    var_dump($servers);
    $name = $input->getArgument('docroot');
    var_dump($name);
  }

//  protected function getCommandName(InputInterface $input) {
//    var_dump($input);
//    return 'Backup OOP2';
//  }

//  protected function getDefaultCommands()
//  {
//    // Keep the core default commands to have the HelpCommand
//    // which is used when using the --help option
//    $defaultCommands = parent::getDefaultCommands();
//
//    //$defaultCommands[] = new MyCommand();
//
//    return $defaultCommands;
//  }

//  public function getDefinition()
//  {
//    $inputDefinition = parent::getDefinition();
//    // clear out the normal first argument, which is the command name
//    $inputDefinition->setArguments();
//
//    return $inputDefinition;
//  }

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