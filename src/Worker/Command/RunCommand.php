<?php

declare(strict_types=1);

namespace Worker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'run')]
class RunCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Run worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Run');

        return Command::SUCCESS;
    }
}