# AuctionX worker

## Requirements

- PHP >= 8.1
- Composer >= 2.3
- Box >= 3.15
- PM2 (https://pm2.keymetrics.io/)

## Install locally

- `git clone git@github.com:getaux/worker.git`
- `composer install && composer bin box install`
- `bin/worker # set your credentials`

## Build locally

- `./vendor/bin/box build`

## Run

![demo-cli](https://user-images.githubusercontent.com/1866496/172454669-531e3c6a-1d2c-43ad-bb38-ff1f33feec99.gif)

1. Setup: locate your executable then run `./auctionx-worker setup`
2. Setup: fill values
3. Run worker: `pm2 start ./auctionx-worker --watch`
4. Stop worker: `pm2 stop auctionx-worker stop`

## License

Licensed under the terms of the MIT License.