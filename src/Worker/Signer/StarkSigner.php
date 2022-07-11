<?php

declare(strict_types=1);

namespace Worker\Signer;

use BIP\BIP44;
use BN\BN;
use Elliptic\Curve\PresetCurve;
use Elliptic\EC;
use Elliptic\EC\KeyPair;
use kornrunner\Serializer\HexSignatureSerializer;

class StarkSigner
{
    public const DEFAULT_SIGNATURE_MESSAGE = 'Only sign this request if you’ve initiated an action with Immutable X.';

    public const DEFAULT_ACCOUNT_APPLICATION = 'immutablex';
    public const DEFAULT_ACCOUNT_LAYER = 'starkex';
    public const DEFAULT_ACCOUNT_INDEX = '1';

    public function __construct(
        private readonly string $publicAddress,
        private readonly string $privateKey,
    ) {
    }

    public function signMessage(string $message): string
    {
        $ethereumSigner = new EthereumSigner($this->privateKey);

        $signature = $ethereumSigner->signMessage(self::DEFAULT_SIGNATURE_MESSAGE);

        $splitEthereumSignature = $this->splitEthereumSignature($signature);
        $derivativePrivateKey = $this->getDerivativePrivateKey($splitEthereumSignature['s'], $this->getPath());
        $grindKey = $this->grindKey($derivativePrivateKey);
        $keyPair = $this->getKeyPair($grindKey);

        return $this->getStarkSignature($keyPair, $message);
    }

    public function getPath(): string
    {
        $layerHash = hash('sha256', self::DEFAULT_ACCOUNT_LAYER);
        $applicationHash = hash('sha256', self::DEFAULT_ACCOUNT_APPLICATION);
        $layerInt = $this->getIntFromBits($layerHash, -31);
        $applicationInt = $this->getIntFromBits($applicationHash, -31);
        $ethAddressInt1 = $this->getIntFromBits($this->publicAddress, -31);
        $ethAddressInt2 = $this->getIntFromBits($this->publicAddress, -62, -31);

        return "m/2645'/" . $layerInt . "'/" . $applicationInt . "'/" . $ethAddressInt1 .
            "'/" . $ethAddressInt2 . "'/" . self::DEFAULT_ACCOUNT_INDEX;
    }

    public function getIntFromBits(string $hex, int $start, int $end = null): int
    {
        $bin = gmp_strval(gmp_init($hex, 16), 2);
        $bits = substr($bin, $start, $end);

        return (int)bindec($bits);
    }

    public function splitEthereumSignature(string $signature): array
    {
        $hexSignature = new HexSignatureSerializer();
        $bin = substr($signature, 0, -2);

        $sign = $hexSignature->parse($bin);

        return [
            'r' => '0x' . str_pad(gmp_strval($sign->getR(), 16), 64, '0', STR_PAD_LEFT),
            's' => '0x' . str_pad(gmp_strval($sign->getS(), 16), 64, '0', STR_PAD_LEFT),
        ];
    }

    public function getDerivativePrivateKey(string $seed, string $path): string
    {
        $seed = substr($seed, 2);
        $hdKey = BIP44::fromMasterSeed($seed)->derive($path);

        // @phpstan-ignore-next-line
        return '0x' . $hdKey->privateKey;
    }

    public function grindKey(string $starkDerivativeKey): string
    {
        $bin = substr($starkDerivativeKey, 2) . '00';

        $bigNumber = new BN(hash('sha256', (string)hex2bin($bin)), 16);
        $newBigNumber = $bigNumber->mod(new BN(
            '08000000 00000010 ffffffff ffffffff b781126d cae7b232 1e66a241 adc64d2f',
            16
        ));

        return substr($newBigNumber->toString(16), 1);
    }

    public function getStarkSignature(KeyPair $keyPair, string $message): string
    {
        // In this case delta will be 4 so we perform a shift-left of 4 bits by adding a ZERO_BN
        $message .= '0';

        $signature = $keyPair->sign($message, 'hex');

        $r = str_pad($signature->r->toString(16), 64, '0', STR_PAD_LEFT);
        $s = str_pad($signature->s->toString(16), 64, '0', STR_PAD_LEFT);

        return '0x' . $r . $s;
    }

    public function getKeyPair(string $privateKey): EC\KeyPair
    {
        $ec = new EC(new PresetCurve([
            'type' => 'short',
            'prime' => null,
            'p' => '800000000000011000000000000000000000000000000000000000000000001',
            'a' => '00000000 00000000 00000000 00000000 00000000 00000000 00000000 00000001',
            'b' => '06f21413 efbe40de 150e596d 72f7a8c5 609ad26c 15c915c1 f4cdfcb9 9cee9e89',
            'n' => '08000000 00000010 ffffffff ffffffff b781126d cae7b232 1e66a241 adc64d2f',
            'hash' => [
                'blockSize' => 512,
                'outSize' => 256,
                'hmacStrength' => 192,
                'padLength' => 64,
                'algo' => 'sha256'
            ],
            'gRed' => false,
            'g' => [
                '1ef15c18599971b7beced415a40f0c7deacfd9b0d1819e03d723d8bc943cfca',
                '5668060aa49730b7be4801df46ec62de53ecd11abe43a32873000c36e8dc1f',
            ],
        ]));

        return $ec->keyFromPrivate($privateKey, 'hex');
    }
}
