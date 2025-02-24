# Alchemy Item Generator

The Alchemy Item Generator is used to generate a set of alchemy items based on user input.

The idea behind this, is a Game Creator can generate their own types of alchemy items based on the type of
alchemy items the game currently supports. This will create a set of new items with random names and descriptions that you
can then export from the admin section as an excel file to then edit and give proper names and descriptions to the items.

## How to use

To use this generator you need to run the following command:

```php
php artisan generate:alchemy-items {amount=25} {minLevel=1} {minCost=100}
```

You will pass in an amount, the min level  and the max level as well as the min cost of the items.

For example with the above defaults, we will create 25 alchemy items that start at level 1 and cost 100 in Gold Dust and Shards
to craft to start. This will then set a max crafting level of 200, so the 25th item will be craftable at level 200 of the alchemy skill.

## Support types of items

When you run the command you will be asked what type of alchemy item you want to create, these types are:

- Stat Increasing
  - Increases the stats of a character
- Damage Increasing
  - Increases the damage amount of a character
- Armour
  - Increases the Armour Class (Defence) of a character
- Healing
  - Increases the healing amount of a character
- Skill Type
  - Allows you to then select the Skill type to increase the skill xp bonus and the general skill bonus (%)
    - Training Skills (those that the character can select to train like Accuracy, Dodge and so on)
    - Crafting Skills (weapon crafting, armour and so on)
    - Enchanting
    - Disenchanting
    - Alchemy
    - Skills that effect the battle timer
    - Skills that effect Directional Movement such as moving North, South, East and West
    - Skills that effect general movement, like teleportation
    - Skills that effect kingdom building timer
    - Skills that effect unit recruitment timer
    - Skills that effect unit movement timer
    - Skills that effect spell evasion
    - Skills that effect kingdoms in general
    - Skills that effect the character class in general (class specific skills)
    - Gem Crafting
- Damages Kingdoms
  - Items that damage a kingdom
- Holy Oils
  - Items that can be applied to other items to increase aspects of the item


