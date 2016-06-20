<?php

namespace DrupalBackup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use DrupalBackup\Exception\BackupException;
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
        $this
            ->setName('dbt:backup')
            ->setDescription('Backup Drupal sites.')
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup specific environments', [])
            ->addOption('site', 'w', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup specific sites', [])
            ->addOption('server', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup from specific servers', [])
            ->addOption('dry-run', 'r', InputOption::VALUE_NONE, 'Shows all Docroots, Servers and Environments that would have been backed up')
            ->addOption('pipe', 'i', InputOption::VALUE_NONE, 'Shows the commands required to run the backup')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'Lists the available sites, servers, and environments to backup')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, the backup will force a new backup into a uniquely named directory')
            ->addOption('backup', 'b', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Select a combination of code, files and db to download only those components', [])
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The SSH Key password to allow automated backup when run non-interactively')
            ->addOption('destination', 'd', InputOption::VALUE_REQUIRED, 'Manually select where to the site backup destination')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command provides a quick and configurable way to backup many Drupal sites without a reliance on tools not commonly installed on remote servers.

Show all possible servers, sites and environments that can be backed up.
  <info>php %command.full_name% --list</info>

Backup all production websites from all servers.
  <info>php %command.full_name% --env prod</info>

Backup the production databases of both 'adammalone_net' and 'example_docroot' websites.
  <info>php %command.full_name% --site adammalone_net --site example_docroot --env prod --backup db</info>

Backup any production website hosted on the 'acquia_server' server.
  <info>php %command.full_name% --server acquia_server --env prod</info>

Create a brand new backup of the production environment from the 'adammalone_net' site using an SSH key password stored in a file.
  <info>php %command.full_name% --env prod --site adammalone_net --force --password "$(< /tmp/keypass)"</info>
EOF
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // A docroot is an environment of a site running on a server e.g. dev.example.com would be the 'dev'
        // environment of the 'example' site running on server 'foo'.
        $docroots = $this->config->getDocroots($input->getOption('server'), $input->getOption('site'), $input->getOption('env'));

        if ($list = $input->getOption('list')) {
            $table = new Table($output);
            $table
                ->setHeaders(['ID (site.environment)', 'Version', 'URL', 'Server']);
        }

        foreach ($docroots as &$docroot) {
            /* @var $docroot DrupalSite */

            if ($list) {
                $table->addRows([[$docroot->getId(), $docroot->getVersion(), $docroot->getUrl(), $docroot->getHostname()]]);
                continue;
            }

            // The user may set defaults in the site yaml file. These may be overwritten
            // using the command line --backup option. If neither are set we default
            // to download everything.
            $docroot->setBackupOptions($input->getOption('backup'));

            // The user may want to create a unique backup. This will ensure that the backup path is iterated through
            // until a unique backup directory is created.
            $docroot->setUnique($input->getOption('force'));

            if ($keypass = $input->getOption('password')) {
                $docroot->setKeypass($keypass);
            }

            // Ask for the SSH key password if one hasn't been provided at the command line. Currently, we force
            // the use of an SSH key password when running non-interactively.
            // @TODO should we allow to run non-interactively without SSH key password?
            if (!$docroot->isKeypassEntered()) {
                if (!$input->isInteractive()) {
                    throw new BackupException('Unable to run non-interactively without the password option.');
                }
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(sprintf("<question>Is the SSH key for %s encrypted?</question> ", $docroot->getHostname()), false);
                if ($helper->ask($input, $output, $question)) {
                    $question = new Question("<question>What is the SSH key password?</question> ");
                    $question->setValidator(function ($value) {
                        if (trim($value) == '') {
                            throw new BackupException('The password can not be empty');
                        }

                        return $value;
                    });
                    $question->setHidden(true);
                    $question->setHiddenFallback(false);
                    $question->setMaxAttempts(3);
                    $password = $helper->ask($input, $output, $question);
                    $docroot->setKeypass($password);
                }
            }

            foreach ($docroot->getBackupOptions() as $component) {
                $output->writeln(sprintf("<comment>Downloading %s</comment>", $component));

                if ($destination = $input->getOption('destination')) {
                    $docroot->setBackupPath($destination);
                }

                // Allow for multiple commands per component.
                $commands = $this->config->getBackupCommand($docroot, $component);

                foreach ($commands as $c) {
                    if ($input->getOption('pipe')) {
                        $output->writeln(sprintf("<comment>%s</comment>", $c));
                    }
                    if ($input->getOption('dry-run')) {
                        continue;
                    }

                    if ($output->isVerbose()) {
                        passthru($docroot->escapeRemoteCommand($c));
                    } else {
                        exec($docroot->escapeRemoteCommand($c));
                    }
                }
                $output->writeln(sprintf("<info>%s downloaded to %s</info>", $component, $docroot->getBackupPath()));
            }

            // @TODO Run some post download clean up/processing?
            // $this->config->postDownload();
        }

        if ($list) {
            $table->render();
        }
    }
}
