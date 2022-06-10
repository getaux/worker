<?php

declare(strict_types=1);

namespace Worker\Signer;

use Elliptic\EC;
use kornrunner\Keccak;

class EthereumSigner
{
    private EC $ec;

    public function __construct(private readonly string $privateKey)
    {
        $this->ec = new EC('secp256k1');
    }

    public function signMessage(string $message): string
    {
        $message = "\x19Ethereum Signed Message:\n" . strlen($message) . $message;
        $hash = Keccak::hash($message, 256);

        $ecPrivateKey = $this->ec->keyFromPrivate($this->privateKey, 'hex');
        $signature = $ecPrivateKey->sign($hash, ['canonical' => true]);

        $r = str_pad($signature->r->toString(16), 64, '0', STR_PAD_LEFT);
        $s = str_pad($signature->s->toString(16), 64, '0', STR_PAD_LEFT);
        $v = dechex($signature->recoveryParam);

        return '0x' . $r . $s . 0 . $v;
    }
}