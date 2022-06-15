<?php

declare(strict_types=1);

namespace Worker\Helper;

use Worker\Exception\SetupException;

class ConfigurationHelper
{
    public static function writeConfiguration(
        string $apiEndpoint,
        string $apiKey,
        string $publicKey,
        string $privateKey
    ): bool
    {
        $credentials = [
            'api_url' => $apiEndpoint,
            'api_key' => $apiKey,
            'public_key' => $publicKey,
            'private_key' => $privateKey,
        ];

        $fileName = self::getFileName();

        $handle = fopen($fileName, 'w+');

        if ($handle) {
            fwrite($handle, (string)json_encode($credentials));
            fclose($handle);

            return true;
        } else {
            return false;
        }
    }

    public static function hasConfiguration(): bool
    {
        $fileName = self::getFileName();

        if (!is_file($fileName)) {
            throw new SetupException('Missing configuration file. Please run "bin/worker setup" command');
        }

        $data = strval(file_get_contents($fileName));
        $configuration = (array)json_decode($data, true);

        $fields = [
            'api_url',
            'api_key',
            'public_key',
            'private_key',
        ];

        foreach ($fields as $field) {
            if (!isset($configuration[$field])) {
                throw new SetupException(sprintf('Missing %s value. Please run "bin/worker setup" command.', $field));
            }
        }

        return true;
    }

    public static function readConfiguration(): array
    {
        $fileName = self::getFileName();

        $data = strval(file_get_contents($fileName));

        return (array)json_decode($data, true);
    }

    public static function getFileName(): string
    {
        return getenv('HOME') ? getenv('HOME') . '/.auctionx/worker' : './.auctionx/worker';
    }

}