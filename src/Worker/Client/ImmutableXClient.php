<?php

declare(strict_types=1);

namespace Worker\Client;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Worker\Signer\EthereumSigner;
use Worker\Signer\StarkSigner;

class ImmutableXClient
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private readonly string $publicKey,
        private readonly string $privateKey,
    )
    {
        $this->httpClient = HttpClient::create();
        /** @todo refactor transfers and test with asset contract */
    }

    public function transferNft(array $payload): array
    {
        $response = $this->httpClient->request('POST', 'https://api.x.immutable.com/v1/signable-transfer-details', [
            'json' => [
                'amount' => '1',
                'receiver' => $payload['recipient'],
                'sender' => $this->publicKey,
                'token' => [
                    'data' => [
                        'token_address' => $payload['asset']['token_address'],
                        'token_id' => $payload['asset']['internal_id'],
                    ],
                    'type' => 'ERC721',
                ]
            ],
        ]);

        $resultSignable = $response->toArray();

        $starkNetSigner = new StarkSigner($this->publicKey, $this->privateKey);
        $starkSignature = $starkNetSigner->signMessage($resultSignable['payload_hash']);

        $ethereumSigner = new EthereumSigner($this->privateKey);

        $response = $this->httpClient->request('POST', 'https://api.x.immutable.com/v1/transfers', [
            'headers' => [
                'x-imx-eth-address' => $this->publicKey,
                'x-imx-eth-signature' => $ethereumSigner->signMessage(
                    $resultSignable['signable_message'],
                ),
            ],
            'json' => [
                'amount' => $resultSignable['amount'],
                'asset_id' => $resultSignable['asset_id'],
                'expiration_timestamp' => $resultSignable['expiration_timestamp'],
                'nonce' => $resultSignable['nonce'],
                'receiver_stark_key' => $resultSignable['receiver_stark_key'],
                'receiver_vault_id' => $resultSignable['receiver_vault_id'],
                'sender_stark_key' => $resultSignable['sender_stark_key'],
                'sender_vault_id' => $resultSignable['sender_vault_id'],
                'stark_signature' => $starkSignature,
            ],
        ]);

        return $response->toArray();
    }

    public function transferToken(array $payload): array
    {
        $response = $this->httpClient->request('POST', 'https://api.x.immutable.com/v1/signable-transfer-details', [
            'json' => [
                'amount' => $payload['token']['quantity'],
                'receiver' => $payload['recipient'],
                'sender' => $this->publicKey,
                'token' => [
                    'data' => [
                        'decimals' => $payload['token']['decimals'],
                    ],
                    'type' => 'ETH',
                ]
            ],
        ]);

        $resultSignable = $response->toArray();

        $starkNetSigner = new StarkSigner($this->publicKey, $this->privateKey);
        $starkSignature = $starkNetSigner->signMessage($resultSignable['payload_hash']);

        $ethereumSigner = new EthereumSigner($this->privateKey);

        $response = $this->httpClient->request('POST', 'https://api.x.immutable.com/v1/transfers', [
            'headers' => [
                'x-imx-eth-address' => $this->publicKey,
                'x-imx-eth-signature' => $ethereumSigner->signMessage(
                    $resultSignable['signable_message'],
                ),
            ],
            'json' => [
                'amount' => $resultSignable['amount'],
                'asset_id' => $resultSignable['asset_id'],
                'expiration_timestamp' => $resultSignable['expiration_timestamp'],
                'nonce' => $resultSignable['nonce'],
                'receiver_stark_key' => $resultSignable['receiver_stark_key'],
                'receiver_vault_id' => $resultSignable['receiver_vault_id'],
                'sender_stark_key' => $resultSignable['sender_stark_key'],
                'sender_vault_id' => $resultSignable['sender_vault_id'],
                'stark_signature' => $starkSignature,
            ],
        ]);

        return $response->toArray();
    }
}