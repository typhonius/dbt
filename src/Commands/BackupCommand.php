<?php

namespace DBT\Commands;

use DBT\Backup\Backup;
use DBT\Config\ConfigLoader;
use DBT\Exception\BackupException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

class BackupCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('backup')
            ->setDescription('Backup Drupal sites.')
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup specific environments', [])
            ->addOption('site', 'w', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup specific sites', ['*'])
            ->addOption('server', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Backup from specific servers', ['*'])
            ->addOption('dry-run', 'r', InputOption::VALUE_NONE, 'Shows all Docroots, Servers and Environments that would have been backed up')
            ->addOption('pipe', 'i', InputOption::VALUE_NONE, 'Shows the commands required to run the backup')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'If set, the backup will force a new backup into a uniquely named directory')
            ->addOption('backup', 'b', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Select a combination of code, files and db to download only those components', ['code', 'db', 'files'])
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The SSH Key password to allow automated backup when run non-interactively')
            ->addOption('destination', 'd', InputOption::VALUE_REQUIRED, 'Manually select where to the site backup destination', 'backups')
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = ConfigLoader::instantiate();
        $filesystem = new Filesystem();

        $backup = new Backup($config, $filesystem, $output);
        $backup->setPassword($input->getOption('password'));

        if (!$input->getOption('password')) {
            if (!$input->isInteractive()) {
                throw new BackupException('Unable to run non-interactively without the password option.');
            }

            $helper = $this->getHelper('question');
            $question = new Question("<question>What is the server/SSH key password?</question> ");
            $question
                ->setHidden(true)
                ->setHiddenFallback(false)
                ->setMaxAttempts(3)
                ->setValidator(function ($value) {
                    if (trim($value) == '') {
                        throw new BackupException('The password can not be empty');
                    }

                    return $value;
                });

            $password = $helper->ask($input, $output, $question);
            $backup->setPassword($password);
        }

        $backup
            ->setEnvironment($input->getOption('env'))
            ->setServer($input->getOption('server'))
            ->setSite($input->getOption('site'))
            ->setBackup($input->getOption('backup'))
            ->setBackupDestination($input->getOption('destination'))
            ->setUnique($input->getOption('force'))
            ->backup();

        return Command::SUCCESS;
    }
}
