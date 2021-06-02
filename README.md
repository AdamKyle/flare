# Flare

Flare is intended to be a simple, old school game where you can chat, fight monsters and rule kingdoms.

The goal of Flare is simple to replicate some of my favourite browser based games that I use to play when I was younger.

For example this game draws heavily on two games:

- [Race War Kingdoms](http://www.glitchless.com/racewarkingdoms.html)
- [Tribal Wars Two](https://www.innogames.com/games/tribal-wars-2/)

If you have ideas for the game please, open a ticket with the appropriate template.

## Features

- Crafting/Enchanting
- Fighting Monsters
- Adventuring
- Kingdom Management
- Market (Player Trade)
- Intractable map.
- Real Time Chat.
- Truly Persistent.

## Common FAQ

- Is this game pay to win? cash shops? Ads?

No, there is no way for you to spend any money in this game. You cannot buy levels, characters, items, nothing. You want it, you earn it.

- How does this game make money if there is no cash shop or ads?

It doesn't. It's a completely free, open source game with no intentions to add any form of 
monetization.

- Are their clans, guilds or resets for the kingdoms or other aspects of the game?

No. It's every person for themselves. There are also no resets.

- Are there energy systems or ways to slow the player down?

No and yes. There are no energy systems, that is there is no feature in game to prevent you from being as active as you want to be. How ever we do make use of timers, these can range from 10 seconds for successfully killing a monster to a few minutes for an adventure to (at most) a couple hours for upgrading buildings (at higher levels) for your kingdom.

The idea is to keep you engaged and playing.

- I can't play all the time, how do I catch up?

There are many ways you can catch up. You could be the type of player who runs adventures all the time - these are the most idle aspect of the game. Maybe you want to rule all the kingdoms on the map, or craft and enchant all the best items and sell them on the market to make a profit.

# Development and Testing

## Getting started with Development:

- `git clone ...`
- cp .env.example .env (see below on websockets and redis)
- `composer install && yarn && php artisan migrate --seed && php artisan create:admin email && yarn dev`
- start redis: eg, `redis-server /usr/local/etc/redis.conf` (you need redis-server and php redis, `pecl install redis`)
- start websockets: `php artisan websocket:serve`
- listen for queues: `php artisan queue:work --queue=high,default --tries=1`
- Publish information section: `php artisan move:files` <sup>**</sup>
- From there you can register as a new player.
  - Or since you ran the `create:admn` command you can reset your admin password and login as admin to make changes to the game<sup>*</sup>.
- Regular players, who sign up, will only see the game section.

<sup>*</sup> See setting up an email below.

<sup>**</sup> The information section is composed of mark down files. This is very experimental at the moment. It takes a series of mark down files, converts them into one document and displays it to the user. The information section is like a Help section.

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

This game, for the admin section at the time of this writing, requires a way to send out emails. 

For example, you can read [here](https://medium.com/@agavitalis/how-to-send-an-email-in-laravel-using-gmail-smtp-server-53d962f01a0c) about setting up gmail with laravel.

## Testing

- `composer phpunit` this will also generate code coverage report.
- `./vendor/bin/phpunit` this will not generate code coverage but can be used for debugging specific tests via the `--filrer=` option
