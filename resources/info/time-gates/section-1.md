# Time Gates

Tlessa does not want to stop you from playing the game. However, I recognize that I could not just let you spam the attack button, 
or move, over and over again. Or worse, write a bot to do it for you - see [Rules of Tlessa](/information/rules).

Instead, what Tlessa has implemented is timers. These are client side timers, fed by server side updates that happen in the background. 
You can switch pages, log out, log in - they are still running - assuming they have not finished.

All timers will be visible to players. You can close a panel that has a timer and re-open it, and it will still be running.

## Actions and Timers

Crafting, Attacking, Adventuring, Moving, Setting Sail, Enchanting, Fighting and Teleporting all currently have timers. Each one varies depending on what it is the timer is for. 

Even [Kingdoms](/information/kingdoms) has their own timers for upgrading, repairing, unit recruitment and unit movement.

All of these are separate. So you can craft, kill a goblin, and move/set sail or start and adventure, for example (in that order - see [Adventure](/information/adventure) section for what happens when you begin an adventure).

Let's go over these:

- **Crafting**: When you craft an item, whether you are successful or not, you will receive a 10-second timer.
- **Fighting**: When fighting, all attacks are done client side. Once you or the creature are dead one of two timers can appear:
  - **If you die**: You will receive a 20-second time out before you can revive.
    - You are considered dead and cannot do a lot of things. See bellow.
  - **If the Monster dies**: You will receive a 10-second time out.
- **Moving**: When a character moves, they will receive a 10-second time out.
- **Setting Sail**: The panel for setting sail will tell you how long in minutes your time out for movement based actions will be.
- **Adventuring**: Depending on levels of adventure the time could vary. Each adventure will tell you how long it will take to completely fulfil.
- **Enchanting**: Can give a 10, 20 or 30 second time out depending on if you are applying one enchant, replacing one enchant or replacing two enchants.
- **Teleporting**: Will give you a time out equal to the time mentioned in the teleport panel when going from one location to another.
- **Traversing**: From one plane to the next, assuming you have the quest item (see [Traversing](/information/traversing)) will give you a 10-second time out for movement based actions.
- **Kingdoms**: These each have their own set of timers:
  - **Building upgrade**: Leveling a building has a timer in relation to minutes. The higher the building level, the longer it takes.
  - **Unit recruitment**: Recruiting units can take seconds, minutes or even hours, depending on the amount of units you are recruiting.
  - **Building Repair**: Has a timer based on current level of building. Repairs can only be done when a building reaches durability of 0. Leveling a damaged building, that is one that has a durability below its max but above 0, will replace current durability with the new max.
  - **Unit Movement**: All units have a moment speed. This speed is applied to the units regardless of how many you send, but stacks with other units that have a slower or equal movement speed. Distance is also taken into consideration.
- **Celestial Fights**: If the [Celestial](/information/celstials) Entity is not killed in one hit, there is a ten-second server time out to allow other players who might have joined a chance to land a hit.
### Character death

Your character can die in a few ways. Fighting monsters and dying in an adventure as well as Celestials killing you. When you die, you cannot:

- Move
- Attack
- Teleport
- Set Sail
- Adventure
- Craft
- Enchant
- Disenchant
- Destroy
- Alchemy
- Equip/Unequip
- Manage [Sets](/information/equipment-sets)
- Sell/Buy from either the shop or market board.
- Traverse
- Destroy items
- Train skills
- Manage kingdoms.
- Speak to [NPC's](/information/npcs)

You will be told to revive and that you are dead and dead people cannot do things.

> ### ATTN!
>
> The Adventuring timer can stop unexpectedly if you have died in the middle of an adventure. 
> 
> Each floor is processed per floor timer length. That is if an adventure has 10 floors at 2 minutes each, every two minutes I process a floor, 
> so if you are expecting the adventure to take twenty minutes and 4 minutes in the timer leaves - chances are - you died, or the adventure took too long.
>
> If that is the case you will be told. You will also receive an email, either way (success/failure) if you are not signed in and have that email setting enabled.

## Timers appear beside the action

All timers will appear beside their corresponding action. Movement and setting sail will also have their own timer appearing beside their respective action button.

Adventuring will also have its timer beside the adventure you are embarking on as well as display notices that you cannot do anything till the adventure is over. Again there are stipulations on what you can do while adventuring, please see [Adventure](/information/adventure) section.

All actions will be disabled for the corresponding timer, for example if you kill a goblin and click "Attack" - while the timer is still active, you'll see a message in chat telling you to wait for the appropriate timer to complete.

### For Kingdoms

Each kingdom on the map, has a management section. Read more in [Kingdoms](/information/kingdoms). In this management section you can see building queues and unit recruitment queues.

You can cancel Building and Unit recruitment.

For unit movement, there is a section in the sidebar for kingdom management, which contains a unit movement section where you can see where the units are going to and even recall them or see if they are returning.

## Chat Throttling

While not a timer, and please do see [Rules of Tlessa](/information/rules), you are throttled on how much you can chat. If you spam the chat box you can be timed out and cannot talk for 5 minutes. You'll see this in chat anytime you try and speak.

You'll first be warned to slow down, then told you cannot speak if you continue in the window it has given you, which is 25 messages in a 1-minute span.

The Creator (me), can also silence you for 10, 30 or 60 minutes.

The time until you can talk again will be displayed in big bright red letters with **your local time (client side)** of when you can talk again.

Upon being able to chat you'll see a server message appear telling you can chat again.

If you are not logged in and have the appropriate settings enabled, you will email you when you can talk again.

> ### ATTN!
> 
> There is no item you can buy to get around timers.
> 
> Should you manage to do too many actions in a short amount of time you will be globally timed out and see a modal that will not 
> disappear, even if you, log out and back in.
> 
> Global timeouts last for two minutes and refresh your screen when they are over.

