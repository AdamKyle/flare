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
- listen for queues: `php artisan horizon`
- Publish information section: `php artisan move:files` <sup>**</sup>
- From there you can register as a new player.
  - Or since you ran the `create:admn` command you can reset your admin password and login as admin to make changes to the game<sup>*</sup>.
- Regular players, who sign up, will only see the game section.

<sup>*</sup> See setting up an email below.

<sup>**</sup> The information section is composed of mark down files. This is very experimental at the moment. It takes a series of mark down files, converts them into one document and displays it to the user. The information section is like a Help section.

## Importing the database

Under `/resources/data-imports/database` is a database called flare.sql.

This contains all the current users of the game, but all their emails and passwords have been masked out and changed for development and 
security purposes. This is the closest approximate of production database.

Once imported you have an Admin user:

- User name: admin@email.com
- Password: TestPassword

All characters have their password set as TestPassword but use random strings for their emails, so you will need to use the admin users table
to find the player you want and then change their email and password.

**No sensitive information is stored in this copy of production database and no PR that has this database changed will be accepted. 
Do not make changes to this export and then re-export it for commit.**

Once you have the database in place, next copy the maps from `/resources/backups/maps` copy the whole directory to Public/Storage/

If you do not have a public storage remember to use: `php artisan storage:link`

Once this is done, next run (in order):

- php artisan cache:clear
- php artisan cache:droppable-items
- php artisan cache:high-end-items

These last two will cache items that can be given to players.

Finally, do `php artisan tinker` and run:

- `resolve(App\Flare\Services\BuildMonsterCacheService::class)->buildCache()`


This allows you to face off against monsters in special locations

With that done. You have the game imported.

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

## Horizon

This game uses' horizon to monitor its jobs. For local development, just go to /horizon.

## Extra Config

In the ENV example file is some extra bits you can set:

- TIME_ZONE: The time zone name you want your game to run in, ie: America/Edmonton (mandatory)
- GITHUB_TOKEN: The token which the app uses to fetch release information.
- HORIZON_EMAIL: For prod, so you do not commit your email in the code base.
- ALLOW_MUlTIPLE_SIGNIN: 1 or 0 only. 1 means yes, up to 10 per IP, 0 means no. 1 per ip. Great for dev.

## Setting up Email:

This game, for the admin section at the time of this writing, requires a way to send out emails. 

For example, you can read [here](https://medium.com/@agavitalis/how-to-send-an-email-in-laravel-using-gmail-smtp-server-53d962f01a0c) about setting up gmail with laravel.

## Testing

- `composer phpunit` this will also generate code coverage report.
- `./vendor/bin/phpunit` this will not generate code coverage but can be used for debugging specific tests via the `--filrer=` option

## Tip For Deploying

- when it comes to the mjml emails you might get an error about how it could not find mjml in your directory where you deployed to.
  All you have to do is install MJML (`npm -i -g mjml`) as a global package.
