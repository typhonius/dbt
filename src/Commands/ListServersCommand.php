<?php

namespace DBT\Commands;

use DBT\Config\ConfigLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListServersCommand extends Command
{
    protected function configure()
    {
        $this->setName('list:servers')
            ->setDescription('Lists servers configured for DBT')
            ->setHelp('Demonstration of custom commands created by Symfony Console component.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['ID', 'Hostname', 'User', 'Port', 'Key']);

        foreach (ConfigLoader::instantiate()->loadServers() as $server) {
            $table->addRows([[$server->machine, $server->hostname, $server->user, $server->port, $server->key]]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
