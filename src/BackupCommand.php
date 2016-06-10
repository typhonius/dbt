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
            ->setName('dbt:backup')
            ->setHelp('DBT provides a quick and configurable way to backup many Drupal sites without a reliance on tools not commonly installed on remote servers.')
            ->setDescription('Backup Drupal sites.')
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup specific environments', array())
            ->addOption('site', 'w', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup specific sites', array())
            ->addOption('server', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup from specific servers', array())
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Shows all Docroots, Servers and Environments that would have been backed up')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, the backup will force a new backup into a uniquely named directory.')
            ->addOption('backup', 'b', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Select a combination of code, files and db to download only those components.', array())
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The SSH Key password to allow automated backup when run non-interactively.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // A docroot is an environment of a site running on a server e.g. dev.example.com would be the 'dev'
        // environment of the 'example' site running on server 'foo'.
        $docroots = $this->config->getDocroots($input->getOption('server'), $input->getOption('site'), $input->getOption('env'));

        $dryrun = $input->getOption('dry-run');

        foreach ($docroots as &$docroot) {
            /* @var $docroot DrupalSite */

            if ($dryrun) {
                $id = $docroot->getId();
                $server = $docroot->getHostname();

                // @TODO provide better information here.
                $output->writeln("<info>ID: ${id} Server: $server</info>");
                continue;
            }

            $output->writeln("<info>Starting backup of {$docroot->getId()}</info>");

            // The user may set defaults in the site yaml file. These may be overwritten
            // using the command line --backup option. If neither are set we default
            // to download everything.
            $docroot->setBackupOptions($input->getOption('backup'));

            $docroot->setUnique($input->getOption('force'));

            if ($keypass = $input->getOption('password')) {
                $docroot->setKeypass($keypass);
            }

            if (!$docroot->isKeypassEntered()) {
                if (!$input->isInteractive()) {
                    throw new \Exception('Unable to run non-interactively without the password option.');
                } else {
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

            $command = [];

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
