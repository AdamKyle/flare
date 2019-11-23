#Flare

Flare is intended to be a simple, old school game where you can chat, fight monsters and rule kingdoms.

This game was inspire by [Racewarkingdoms](http://racewarkingdoms.com) as well as [Tribal Wars 2](https://us.tribalwars2.com)

## Getting started with Development:

- `git clone ...`
- cp .env.example .env (see below on websockets)
- `composer install && yarn && php artisan migrate --seed && php artisan create:admin email && yarn dev`
- Log in as an admin (you will have to reset your password first), you will be known as "God" when chatting and will be able to view the dashboard.
- Regular players, who sign up, will only see the game section.

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
