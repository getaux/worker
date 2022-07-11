<?php

declare(strict_types=1);

namespace Worker\Helper;

class TokenHelper
{
    public const ERC20_TOKENS = [
        'IMX' => '0xf57e7e7c23978c3caec3c3548e3d615c346e79ff',
        'USDC' => '0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48',
        'GODS' => '0xccc8cb5229b0ac8069c51fd58367fd1e622afd97',
        'GOG' => '0x9ab7bb7fdc60f4357ecfef43986818a2a3569c62',
        'OMI' => '0xed35af169af46a02ee13b9d79eb57d6d68c1749e',
    ];

    public static function getTokenContract(string $currency): string
    {
        return self::ERC20_TOKENS[$currency] ?? $currency;
    }
}
