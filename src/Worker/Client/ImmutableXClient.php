<?php

declare(strict_types=1);

namespace Worker\Client;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Worker\Helper\TokenHelper;
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

        return $this->transfer($response->toArray());
    }

    public function transferToken(array $payload): array
    {
        $tokenData = [
            'decimals' => $payload['token']['decimals'],
        ];

        $tokenType = 'ETH';

        if ($payload['token']['token_type'] !== 'ETH') {
            $tokenType = 'ERC20';
            $tokenData['token_address'] = TokenHelper::getTokenContract($payload['token']['token_type']);
        }

        $response = $this->httpClient->request('POST', 'https://api.x.immutable.com/v1/signable-transfer-details', [
            'json' => [
                'amount' => $payload['token']['quantity'],
                'receiver' => $payload['recipient'],
                'sender' => $this->publicKey,
                'token' => [
                    'data' => $tokenData,
                    'type' => $tokenType,
                ],
            ],
        ]);

        return $this->transfer($response->toArray());
    }

    private function transfer(array $resultSignable): array
    {
        // sign transfer
        $ethereumSigner = new EthereumSigner($this->privateKey);
        $ethereumSignature = $ethereumSigner->signMessage($resultSignable['signable_message']);

        // generate stark_signature
        $starkNetSigner = new StarkSigner($this->publicKey, $this->privateKey);
        $starkSignature = $starkNetSigner->signMessage($resultSignable['payload_hash']);

        $response = $this->httpClient->request('POST', 'https://api.x.immutable.com/v1/transfers', [
            'headers' => [
                'x-imx-eth-address' => $this->publicKey,
                'x-imx-eth-signature' => $ethereumSignature,
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