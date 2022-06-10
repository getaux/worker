<?php

declare(strict_types=1);

namespace Worker\Command;

use kornrunner\Keccak;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Web3p\EthereumUtil\Util;
use Worker\Exception\SetupException;
use Worker\Helper\ConfigurationHelper;

#[AsCommand(name: 'setup')]
class SetupCommand extends Command
{
    private HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Setup AuctionX worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->displayLogo($io);

        try {
            $apiEndpoint = $this->getApiUrl($io);
            $apiKey = $this->getApiKey($io, $apiEndpoint);
            $keys = $this->getKeys($io, $apiEndpoint);

            $table = new Table($io);
            $table->setHeaders(['Field', 'Value'])
                ->setRows([
                    ['Api URL', $apiEndpoint],
                    ['Api Key', $this->hideField($apiKey)],
                    ['Public Key', $keys['publicKey']],
                    ['Private Key', $this->hideField($keys['privateKey'])],
                ]);
            $table->render();

            $write = ConfigurationHelper::writeConfiguration(
                $apiEndpoint,
                $apiKey,
                $keys['publicKey'],
                $keys['privateKey']
            );

            if ($write) {
                $io->success('Configuration saved! You can now run the worker to execute transfers');
            } else {
                $io->error('Error while trying to save configuration');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function displayLogo(StyleInterface $io): void
    {
        $logo = <<<EOF
        
                                             _   _            __   __
                             /\             | | (_)           \ \ / /
                            /  \  _   _  ___| |_ _  ___  _ __  \ V / 
                           / /\ \| | | |/ __| __| |/ _ \| '_ \  > <  
                          / ____ \ |_| | (__| |_| | (_) | | | |/ . \ 
                         /_/    \_\__,_|\___|\__|_|\___/|_| |_/_/ \_\
                        
                                         Worker setup       
                                         
                                            
        
        EOF;

        $io->text($logo);
    }

    private function getApiUrl(StyleInterface $io): string
    {
        // ask for api endpoint
        $apiEndpoint = strval($io->ask(
            'Please provide endpoint "Ping" of your API (<fg=yellow>e.g. https://api.yoursite.com/v1/ping</>)',
            null,
            function ($url) {
                return filter_var($url, FILTER_VALIDATE_URL);
            }
        ));

        // url is malformed
        if ($apiEndpoint === '') {
            throw new SetupException('Invalid format url');
        }

        // try the endpoint
        $response = $this->httpClient->request('GET', $apiEndpoint);
        $result = $response->toArray();

        if ($result['result'] !== 'OK') {
            throw new SetupException('Error while fetching api endpoint');
        }

        return str_replace('/ping', '', $apiEndpoint);
    }

    private function getApiKey(StyleInterface $io, string $apiEndpoint): string
    {
        // ask for api endpoint
        $apiKey = strval($io->askHidden('Please provide your API Key (<fg=yellow>hidden in CLI</>)'));

        // try the messages endpoint
        $response = $this->httpClient->request('OPTIONS', $apiEndpoint . '/messages', [
            'headers' => [
                'x-api-key' => $apiKey,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new SetupException('Error while fetching api endpoint');
        }

        return $apiKey;
    }

    private function getKeys(StyleInterface $io, string $apiEndpoint): array
    {
        $privateKey = strval(
            $io->askHidden('Please provide private key of your escrow wallet (<fg=yellow>hidden in CLI</>)')
        );

        $ellipticCurve = new Util();

        $publicKeyHex = $ellipticCurve->privateKeyToPublicKey($privateKey);

        // We need to remove the leading 0x04 in order to hash it correctly
        $publicKeyHex = substr($publicKeyHex, 4);

        $hash = Keccak::hash(hex2bin($publicKeyHex), 256);

        // ETH address has 20 bytes length and 40 has hex characters long,
        // and we only need the last 20 bytes as Ethereum address
        $walletAddress = strtolower('0x' . substr($hash, -40));

        // try the messages endpoint
        $response = $this->httpClient->request('GET', $apiEndpoint . '/wallet');
        $result = $response->toArray();

        if (!isset($result['publicKey'])) {
            throw new SetupException('Internal error, please try again');
        }

        if (strtolower($result['publicKey']) !== $walletAddress) {
            throw new SetupException(sprintf('Private key must match with wallet address %s', $result['publicKey']));
        }

        return [
            'publicKey' => $walletAddress,
            'privateKey' => $privateKey,
        ];
    }

    private function hideField(string $value): string
    {
        return substr($value, 0, 4) . str_repeat('*', strlen($value) - 4);
    }
}