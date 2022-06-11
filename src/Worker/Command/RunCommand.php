<?php

declare(strict_types=1);

namespace Worker\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Worker\Client\ImmutableXClient;
use Worker\Helper\ConfigurationHelper;

#[AsCommand(name: 'run')]
class RunCommand extends Command
{
    const ALLOWED_TASKS = [
        'task-transfer-nft',
        'task-transfer-token',
    ];

    const STATUS_OK = 'OK';
    const STATUS_KO = 'KO';

    private HttpClientInterface $httpClient;
    private readonly array $credentials;
    private readonly ImmutableXClient $immutableXClient;

    private SymfonyStyle $io;

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
        $this->io = new SymfonyStyle($input, $output);

        return $this->runProcess();
    }

    private function runProcess(): int
    {
        // fetch message
        $response = $this->httpClient->request('GET', $this->credentials['api_url'] . '/messages', [
            'headers' => [
                'x-api-key' => $this->credentials['api_key'],
            ],
        ]);

        $result = $response->toArray();

        if (!isset($result['message'])) {
            // nothing to do
            $this->io->writeln($this->formatLog(sprintf('Nothing to process')));

            // avoid API DDoS
            sleep(5);

            return Command::SUCCESS;
        } else {
            $message = $result['message'];
        }

        if (in_array($message['task'], self::ALLOWED_TASKS)) {
            try {
                switch ($message['task']) {
                    case 'task-transfer-nft':
                        $response = $this->immutableXClient->transferNft($message['body']);
                        break;
                    case 'task-transfer-token':
                        $response = $this->immutableXClient->transferToken($message['body']);
                        break;
                }

                $this->postResponse($message['id'], (array)$response);
                return Command::SUCCESS;

            } catch (\Exception $e) {
                // send errored process
                $this->postResponse($message['id'], $e->getMessage(), self::STATUS_KO);
                return Command::FAILURE;
            }
        } else {
            // something almost impossible
            $this->postResponse(
                $message['id'],
                sprintf('Unknown task %s', $message['task']),
                self::STATUS_KO
            );
            return Command::FAILURE;
        }
    }

    private function postResponse(int $id, array|string $response, string $status = self::STATUS_OK): void
    {
        $this->httpClient->request('POST', $this->credentials['api_url'] . '/messages', [
            'headers' => [
                'x-api-key' => $this->credentials['api_key'],
            ],
            'json' => [
                'message_id' => $id,
                'response' => [
                    ($status === self::STATUS_OK ? 'response' : 'error') => $response
                ],
                'status' => $status
            ],
        ]);

        // log task
        $this->io->writeln($this->formatLog(sprintf(
            'Task %s - Status %s',
            $id,
            $status
        )));
    }

    private function formatLog(string $message): string
    {
        return date('Y-m-d H:i:s') . ' ' . $message;
    }
}