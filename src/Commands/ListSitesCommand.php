<?php

namespace DBT\Commands;

use DBT\Config\ConfigLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListSitesCommand extends Command
{
    protected function configure()
    {
        $this->setName('list:sites')
            ->setDescription('Lists sites configured for DBT')
            ->setHelp('Demonstration of custom commands created by Symfony Console component.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['ID (site.environment)', 'Version', 'URL', 'Server']);

        foreach (ConfigLoader::instantiate()->loadSites() as $site) {
            foreach ($site->getEnvironments() as $environment) {
                $table->addRows([[$environment->id, $site->version, $environment->url, $environment->server]]);
            }
        }

        $table->render();
        return Command::SUCCESS;
    }
}
