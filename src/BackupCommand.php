<?php

namespace DrupalBackup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use DrupalBackup\BackupConfig\DrupalConfigBaseInterface;
use DrupalBackup\Exception\DatabaseDriverNotSupportedException;

/**
 * Class BackupCommand
 * @package DrupalBackup
 */
class BackupCommand extends Command
{
    /**
     * @var DrupalConfigBaseInterface $config
     */
    private $config;

    /**
     * BackupCommand constructor.
     * @param DrupalConfigBaseInterface $config
     */
    public function __construct(DrupalConfigBaseInterface $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName("dbt:backup")
            ->setHelp("Help will go here")// TODO Maybe use a function to return help.
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $class = $this->config->getBackupClass();

        if ($input->getOption('show')) {
            $all = new $class(array(), array(), array());
            $output->writeln('<info>Information about all resources will go here.</info>');

            return;
        }

        $this->runBackup($input, $output);

    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws DatabaseDriverNotSupportedException
     * @throws \Exception
     * @throws Exception\Ssh2ConnectionException
     */
    private function runBackup(InputInterface $input, OutputInterface $output)
    {

        $docroots = $this->config->getDocroots($input->getOption('servers'), $input->getOption('docroots'), $input->getOption('envs'));

        foreach ($docroots as &$docroot) {
            /* @var $docroot DrupalSite */

            $output->writeln("<info>Starting backup of {$docroot->id}</info>");
            if ($input->isInteractive()) {
                if (!$docroot->isKeypassEntered()) {
                    $helper = $this->getHelper('question');
                    $question = new ConfirmationQuestion("<question>Is the SSH key for {$docroot->getHostname()} encrypted?</question> ", false);
                    if ($helper->ask($input, $output, $question)) {
                        $question = new Question("<question>What is the SSH key password?</question> ");
                        $question->setHidden(true);
                        $question->setHiddenFallback(false);
                        $password = $helper->ask($input, $output, $question);
                        $docroot->setKeypass($password);
                    }
                }
            }

            // The user may set defaults in the site yaml file. These may be overwritten
            // using the command line --backup option. If neither are set we default
            // to download everything.
            $docroot->setBackupOptions($input->getOption('backup'));

            $docroot->setUnique($input->getOption('force'));

            $command = [];
            // @TODO get conf_path()
            // include sites all

            if (in_array('files', $docroot->getBackupOptions())) {
                $command['files'] = $this->config->getBackupCommand($docroot, 'files');
            }
            if (in_array('code', $docroot->getBackupOptions())) {
                $command['code'] = $this->config->getBackupCommand($docroot, 'code');
            }
            if (in_array('db', $docroot->getBackupOptions())) {
                $command['db'] = $this->config->getBackupCommand($docroot, 'db');
            }

            foreach ($command as $stage => $c) {
                // try catch here to get as much even if one fails
                $output->writeln(sprintf("<comment>Downloading %s</comment>", $stage));
                if ($output->isVerbose()) {
                    passthru($c['command']);
                } else {
                    exec($c['command']);
                }
                // @TODO use $this->config->getBackupLocation()
                $output->writeln(sprintf("<info>%s downloaded to %s</info>", $stage, $c['backup_location']));
            }
        }
    }
}
