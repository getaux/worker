<?php

declare(strict_types=1);

namespace Tests\Worker\Signer;

use PHPUnit\Framework\TestCase;
use Worker\Signer\StarkSigner;

class StarkSignerTest extends TestCase
{
    public function setUp(): void
    {
        // avoid wrong dependencies warning
        error_reporting(E_ALL ^ E_DEPRECATED);

        // ethereum keypair
        $this->publicKey = '0xda0c92e9e1371e4cdf420f87fb05351fd1239e10';
        $this->privateKey = '0x4a51402c02335aef74e17ea176e7121ab12f44a1da32f9096c02a16806511c5e';

        $this->starkNetSigner = new StarkSigner(
            $this->publicKey,
            $this->privateKey
        );
    }

    public function testSplitEthereumSignature(): void
    {
        // generated from EthereumSigner::signMessage
        $signature = '0x434dbe1dadfc1119bb8ac8471ed54bed88845b518f441e90bef13fcb470d780363e4dbd8828d037751b2e38f6e329f9f8562b447fe6dcaec0bb7ad9ac0fc137601';

        $splitSignature = $this->starkNetSigner->splitEthereumSignature($signature);

        $this->assertSame(
            '0x63e4dbd8828d037751b2e38f6e329f9f8562b447fe6dcaec0bb7ad9ac0fc1376',
            $splitSignature['s']
        );
    }

    public function testGetPath(): void
    {
        $path = $this->starkNetSigner->getPath();

        $this->assertSame(
            "m/2645'/579218131'/211006541'/1361288720'/1980394047'/1",
            $path
        );
    }

    public function testGetDerivativePrivateKey(): void
    {
        // generated from StarkNetSigner::SplitEthereumSignature
        $seed = '0x63e4dbd8828d037751b2e38f6e329f9f8562b447fe6dcaec0bb7ad9ac0fc1376';
        // generated from StarkNetSigner::getPath
        $path = "m/2645'/579218131'/211006541'/1361288720'/1980394047'/1";

        $derivativeKey = $this->starkNetSigner->getDerivativePrivateKey($seed, $path);

        $this->assertSame(
            '0xdcc9806c78277a47a65780f1c791755eaf3db6c1d62c1b454628efc90801e0b0',
            $derivativeKey
        );
    }

    public function testGrindKey(): void
    {
        // generated from StarkNetSigner::getDerivativePrivateKey
        $derivativeKey = '0xdcc9806c78277a47a65780f1c791755eaf3db6c1d62c1b454628efc90801e0b0';

        $keyPair = $this->starkNetSigner->grindKey($derivativeKey);

        $this->assertSame(
            '16a9fea4a9823b819cae05462cf407a154098a08b3cd1197fa6f2b155d50634',
            $keyPair
        );
    }

    public function testSignMessage(): void
    {
        $signature = $this->starkNetSigner->signMessage('30b16a8caacbdb28f1b4ab1f1e745f3055ca7bd5726dc8cdd2f67cf18faedca');

        $this->assertSame(
            '0x05df23dec2874351cbe5a12a532e4aa7dfc8f411a9cead617280ce826c64b60c079c98570dd6556e4cf3531d295b5ad0087d963e0a6797421084991d890dd243',
            $signature
        );
    }
}