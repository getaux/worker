<?php

declare(strict_types=1);

namespace Worker;

use Symfony\Component\Console\Application;
use Worker\Command\RunCommand;
use Worker\Command\SetupCommand;
use Worker\Exception\SetupException;
use Worker\Helper\ConfigurationHelper;

class App
{
    private Application $application;

    public function __construct()
    {
        $this->application = new Application();

        $this->application->add(new SetupCommand());
        $this->application->add(new RunCommand());

        try {
            ConfigurationHelper::hasConfiguration();
            $this->application->setDefaultCommand('run');
        } catch (SetupException $exception) {
            $this->application->setDefaultCommand('setup');
        }

        $this->application->run();
    }
}