# Flare

Flare is intended to be a simple, old school game where you can chat, fight monsters and rule kingdoms.

The goal of Flare is simple to replicate some of my favourite browser based games that I use to play when I was younger.

For example this game draws heavily on two games:

- [Race War Kingdoms](http://www.glitchless.com/racewarkingdoms.html)
- [Tribal Wars Two](https://www.innogames.com/games/tribal-wars-2/)

One could argue that making a game is very hard and takes a lot of time and dedication.

Those people would be right. I fully intend to finish and launch this yet to be titled game.

If you have ideas open a ticket with the title: `Idea for Game Name`

This game is heavily under development but some of the features that you will get to experience are:

# Development and Testing

## Getting started with Development:

- `git clone ...`
- cp .env.example .env (see below on websockets and redis)
- `composer install && yarn && php artisan migrate --seed && php artisan create:admin email && yarn dev`
- start redis: eg, `redis-server /usr/local/etc/redis.conf`
- start websockets: `php artisan websocket:serve`
- listen for queues: `php artisan queue:work --queue=high,default --tries=1`
- Publish information section: `php artisan move:files` <sup>**</sup>
- From there you can register as a new player.
  - Or since you ran the `create:admn` command you can reset your admin password and login as admin to make changes to the game<sup>*</sup>.
- Regular players, who sign up, will only see the game section.

<sup>*</sup> See setting up an email below.

<sup>**</sup> The information section is comprised of mark down files. This is very experiemental at the moment. It takes a series of mark down files, converts them into one document and displays it to the user. The information section is like a Help section.

## Redis

We use redis for jobs and queues with in the system. To get started, make sure you have php redis installed, the redis server and that its running.

Next update the .env file with:

```
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=yourpassword
REDIS_PORT=6379
REDIS_CLIENT=phpredis
REDIS_CLUSTER=phpredis
```

then run: `php artisan queue:work --queue=high,default --tries=1`

## Websockets

This game depends heavily on websockets for almost everything we do. With that said to get started all you have to do is set the following in the env
and then start the websocket server:

```
BROADCAST_DRIVER=pusher
...
PUSHER_APP_ID=test
PUSHER_APP_KEY=test
PUSHER_APP_SECRET=test
PUSHER_APP_CLUSTER=mt1
```

## Setting up Email:

This game, for the admin section at the time of this writing, requires a way to send out emails. For example you can read [here](https://medium.com/@agavitalis/how-to-send-an-email-in-laravel-using-gmail-smtp-server-53d962f01a0c) about setting up gmail with laravel.

## Telescope for development

We use Laravels Telescope to monitor jobs and queues as well as a few other things when developing to help make sure things are working smoothly.

You can enable this in the env file when you are developing.

If you make changes to a job or event, make sure to restart not just the queue, but the web sockets as well.

## Testing

- `composer phpunit` this will also generate code coverage report.
- `./vendor/bin/phpunit` this will not generate code coverage but can be used for debugging specific tests via the `--filrer=` option
