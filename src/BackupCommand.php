<?php

namespace DrupalBackup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use DrupalBackup\BackupConfig\DrupalConfigBaseInterface;

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
        // @TODO should backup desination for local be configurable with command line options?
        $this
            ->setName('dbt:backup')
            ->setHelp('DBT provides a quick and configurable way to backup many Drupal sites without a reliance on tools not commonly installed on remote servers.')
            ->setDescription('Backup Drupal sites.')
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup specific environments', array())
            ->addOption('site', 'w', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup specific sites', array())
            ->addOption('server', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup from specific servers', array())
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Shows all Docroots, Servers and Environments that would have been backed up')
//            ->addOption('pipe', 'i', InputOption::VALUE_NONE, 'Shows the commands required to run the backup')
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
//        $pipe = $input->getOption('pipe');

        foreach ($docroots as &$docroot) {
            /* @var $docroot DrupalSite */

            // @TODO provide better information here.
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
                    // @TODO allow to run interactively without SSH key password.
                    throw new \Exception('Unable to run non-interactively without the password option.');
                } else {
                    $helper = $this->getHelper('question');
                    $question = new ConfirmationQuestion(sprintf("<question>Is the SSH key for %s encrypted?</question> ", $docroot->getHostname()), false);
                    if ($helper->ask($input, $output, $question)) {
                        $question = new Question("<question>What is the SSH key password?</question> ");
                        $question->setHidden(true);
                        $question->setHiddenFallback(false);
                        $password = $helper->ask($input, $output, $question);
                        $docroot->setKeypass($password);
                    }
                }
            }

            foreach ($docroot->getBackupOptions() as $component) {
                $output->writeln(sprintf("<comment>Downloading %s</comment>", $component));

                if ($dryrun) {
                    continue;
                }

                // Allow for multiple commands per component.
                $commands = $this->config->getBackupCommand($docroot, $component);

                foreach ($commands as $c) {
//                    if ($pipe) {
//                        $output->writeln(sprintf("<comment>%s</comment>", $c));
//                    }

                    if ($output->isVerbose()) {
                        passthru($c);
                    } else {
                        exec($c);
                    }
                }
                $output->writeln(sprintf("<info>%s downloaded to %s</info>", $component, $docroot->getBackupPath()));

            }

            // @TODO Run some post download clean up/processing?
            // $this->config->postDownload();
        }
    }
}
