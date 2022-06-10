<?php

declare(strict_types=1);

namespace Tests\Worker\Signer;

use PHPUnit\Framework\TestCase;
use Worker\Signer\EthereumSigner;
use Worker\Signer\StarkSigner;

class EthereumSignerTest extends TestCase
{
    public function testStarkSignature()
    {
        error_reporting(E_ALL ^ E_DEPRECATED);

        $privateKey = '0x4a51402c02335aef74e17ea176e7121ab12f44a1da32f9096c02a16806511c5e';

        $ethereumSigner = new EthereumSigner($privateKey);
        $signatureHash = $ethereumSigner->signMessage(StarkSigner::DEFAULT_SIGNATURE_MESSAGE);

        $this->assertSame(
            '0x434dbe1dadfc1119bb8ac8471ed54bed88845b518f441e90bef13fcb470d780363e4dbd8828d037751b2e38f6e329f9f8562b447fe6dcaec0bb7ad9ac0fc137601',
            $signatureHash
        );
    }
}