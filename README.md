<p align="center">
    <img width="100" src="https://user-images.githubusercontent.com/1866496/173375782-cf5bcb4e-8e7d-4e0f-984f-fef5202362a0.png"/>
</p>

<h1 align="center">AuctionX worker</h1>

## Requirements

- [PHP](https://www.php.net/) >= 8.1
- [Composer](https://getcomposer.org/) >= 2.3
- [Box](https://github.com/box-project/box) >= 3.15
- [PM2](https://pm2.keymetrics.io/) (recommended) or supervisord

## Install locally

- `git clone git@github.com:getaux/worker.git`
- `composer install`
- `bin/worker`
- Then fill credentials

## Unit tests

- `composer test`

## Build locally

- `composer build`

## Setup

![demo-cli](https://user-images.githubusercontent.com/1866496/172454669-531e3c6a-1d2c-43ad-bb38-ff1f33feec99.gif)

1. Locate your executable then run `./auctionx-worker setup`
2. Fill values

## Run

- Start worker: `pm2 start ./auctionx-worker --watch`
- Stop worker: `pm2 stop auctionx-worker stop`

## License

Licensed under the terms of the MIT License.