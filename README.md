# Flare

![badge](https://action-badges.now.sh/AdamKyle/flare?action=test)

Flare is intended to be a simple, old school game where you can chat, fight monsters and rule kingdoms.

The goal of Flare is simple to replicate some of my favourite browser based games that I use to play when I was younger.

For example this game draws heavily on two games:

- [Race War Kingdoms](http://www.glitchless.com/racewarkingdoms.html)
- [Tribal Wars Two](https://www.innogames.com/games/tribal-wars-2/)

One could argue that making a game is very hard and takes a lot of time and dedication.

Those people would be right. I fully intend to finish and launch this yet to be titled game.

If you have ideas open a ticket with the title: `Idea for Game Name`

This game is heavily under development but some of the features that you will get to experience are:

# Adventure

- Pick a race and a class and begin your adventure!
- Move around on a 2000x2000 map that is completely draggable. Explore caves, ancient ruins and more with time based mechanics.
- Fight monsters, gain loots, gold, experience and levels!
- Equip your gear and become stronger. Play to your classes strengths and become the strongest character!
- Chat in real time with other people including private messages.
- More to come ...

# Kingdoms

- Start off with a predefined kingdom on a giant map that can have over 250 thousand kingdoms!
- Grow your kingdoms, store your gold, rule over other kingdoms in a time based way.
- Start or Join a clan, conquer the map and farm the plundered villages for scraps of resources.
- Recruit, build up and gather resources.
- More the come ...


This game is completely free and any special gear or bonuses that are similar to the two games mentioned above
can all be gained in game with no need to spend out side money. This point was very important to me.

# Development and Testing

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

## Testing

- `composer phpunit`

We exclude the `App/Flare/MapGenerator` from the tests because this module takes way too much memory and
would also slow the testing down. We do have a command (see above) `php artisan create:map` that is guaranteed to work.

We also do not test Commands or Mail as neither are used, accept the create admin account command.
