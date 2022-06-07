<?php

declare(strict_types=1);

namespace Worker;

use Symfony\Component\Console\Application;
use Worker\Command\{RunCommand, SetupCommand};

class App
{
    private Application $application;

    public function __construct()
    {
        $this->application = new Application();

        $this->application->add(new SetupCommand());
        $this->application->add(new RunCommand());

        $this->getCredentials();

        $this->application->run();
    }

    private function getCredentials(): void
    {
        $fileName = getenv('HOME') ? getenv('HOME') . '/.auctionx-config' : './.auctionx-config';

        $this->application->setDefaultCommand('setup');

        if (!is_file($fileName)) {

        } else {
            $fileData = file_get_contents($fileName);
            $credentials = json_decode((string)$fileData, true);

            /** @todo finish me */

            $this->application->setDefaultCommand('run');
        }
    }
}