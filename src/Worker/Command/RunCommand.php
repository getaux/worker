<?php

declare(strict_types=1);

namespace Worker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Worker\Client\ImmutableXClient;
use Worker\Helper\ConfigurationHelper;

#[AsCommand(name: 'run')]
class RunCommand extends Command
{
    const ALLOWED_TASKS = [
        'task-transfer-nft',
        'task-transfer-crypto',
    ];

    private HttpClientInterface $httpClient;
    private readonly array $credentials;
    private readonly ImmutableXClient $immutableXClient;

    public function __construct()
    {
        $this->credentials = ConfigurationHelper::readConfiguration();
        $this->httpClient = HttpClient::create();

        $this->immutableXClient = new ImmutableXClient(
            $this->credentials['public_key'],
            $this->credentials['private_key']
        );

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Run worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fetchMessage();

        return Command::SUCCESS;
    }

    private function fetchMessage(): void
    {
        $response = $this->httpClient->request('GET', $this->credentials['api_url'] . '/messages', [
            'headers' => [
                'x-api-key' => $this->credentials['api_key'],
            ],
        ]);

        $result = $response->toArray();

        if (!isset($result['message'])) {
            /** catch error */
            throw new \Exception('Error todo');
        } else {
            $message = $result['message'];
        }

        if (in_array($message['task'], self::ALLOWED_TASKS)) {
            switch ($message['task']) {
                case 'task-transfer-nft':
                    $this->immutableXClient->transferNft($message['body']);
                    break;
                case 'task-transfer-crypto':
                    $this->immutableXClient->transferCrypto($message['body']);
                    break;
            }
        }
    }
}