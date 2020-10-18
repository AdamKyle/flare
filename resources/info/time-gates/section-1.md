# Time Gates

Tlessa does not want to stop you from playing the game. How ever, we recognize that we could not just let you spam the attack button, or move, over and over again. Or worse, write a bot to do it for you - see [Rules of Tlessa]().

Instead what Tlessa has implemented is timers. These are client side timers, fed by server side updates that happen in the background. You can switch pages, log out, log in - they are still running - assuming they have not finished.

All timers will be visible to players. You can close a panel that has a timer and re-open it and it will still be running.

## Actions and Timers

Crafting, Attacking, Adventuring, Moving, Setting Sail and Adventuring all currently have timers. Each one veries depending on what it is the timer is for. 

All of these are seperate. So you can craft, kill a goblin, and move/set sail or start and adventure, for example (in that order - see [Adventure](/information/adventure) section for what happens when you begin an adventure).

Lets go over these:

- Crafting: When you craft an item, wether you are sucessful or not, you will recieve a 10 second timer.
- Fighting: When fighting, all attacks are done client side. Once you or the creature are dead one of two timers can appear:
  - **If you die**: You will recieve a 20 second time out before you can revive.
    - You are considered dead and cannot do a lot of things. See [Character Information](/information/character-information).
  - **If the Monster dies**: You will recieve a ten second time out.
- Moving: When a character moves, they will recieve a 10 second time out.
- Setting Sail: The pannel for setting sail will tell you how long in minutes your time out for movement based actions will be.
- Adventuring: Depending on levels of adventure the time could vary. Each adventure will tell you how long it will take to completly fufil.

> ### ATTN!
>
> The Adventuring timer can stop unexpectly if you have died in the middle of an adventure. Each floor is processed per floor timer length. That is if a adventure has 10 floors at 2 minutes each, every two minutes we process a floor, so if you are expecting the adventure to take twenty minutes and 4 minutes in the timer leaves - chances are - you died.

## Timers appear beside the action

All timers will appear beside their coresponding action. Movement and setting sail will also have their own timer appearing beside their respecitve action button.

Adevturing will also have its timer beside the adventure you are embarking on as well as display notices that you cannot do anything till the adventure is over. Again there are stipulations on what you can do while adventuring, please see [Adventure](/information/adventure) section.

All actions will be disabled for the coresponding timer, for example if you kill a goblin and click "Atack" - while the timer is still active, youll see a message in chat telling you to wait for the appropriate timer to complete.

## Chat Throttling

While not a timer, and please do see [Rules of Tlessa](), you are throttled on how much you can chat. If you spam the chat box you can be timed out and cannot talk for 5 minutes. Youll see this in chat anytime you try and speak.

You'll first be warned to slow down, then told you cannot speak if you continue in the window it has given you, which is 25 messages in a 2 minute span.

The Creator, can also silence you for 10, 30 or 60 minutes.

The time until you can talk again will be disaplyed in big bright red letters with your local time (client side) of when you can talk again.

Upon being able to chat you'll see a server message appear telling you can chat again.


