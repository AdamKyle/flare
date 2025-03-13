import SelectOption from "../types/select-option";

export const buildLivewireTableOptions = (): SelectOption[] => {
    return [
        {
            label: "Please select",
            value: "",
        },
        {
            label: "Items",
            value: "admin.items.items-table",
        },
        {
            label: "Races",
            value: "admin.races.races-table",
        },
        {
            label: "Classes",
            value: "admin.classes.classes-table",
        },
        {
            label: "Monsters",
            value: "admin.monsters.monsters-table",
        },
        {
            label: "Celestials",
            value: "admin.monsters.celestials-table",
        },
        {
            label: "Quest items",
            value: "info.quest-items.quest-items-table",
        },
        {
            label: "Crafting Books",
            value: "info.quest-items.crafting-books-table",
        },
        {
            label: "Craftable Items",
            value: "info.items.craftable-items-table",
        },
        {
            label: "Hell Forged Items",
            value: "info.items.hell-forged",
        },
        {
            label: "Purgatory Chains Items",
            value: "info.items.purgatory-chains",
        },
        {
            label: "Pirate Lord Leather",
            value: "info.items.pirate-lord-leather",
        },
        {
            label: "Corrupted Ice",
            value: "info.items.corrupted-ice",
        },
        {
            label: "Twisted Earth",
            value: "info.items.twisted-earth",
        },
        {
            label: "Delusional Silver",
            value: "info.items.delusional-silver",
        },
        {
            label: "Faithless Plate",
            value: "info.items.faithless-plate",
        },
        {
            label: "Ancestral Items",
            value: "info.items.ancestral-items",
        },
        {
            label: "Craftable Trinkets",
            value: "info.items.craftable-trinkets",
        },
        {
            label: "Enchantments",
            value: "admin.affixes.affixes-table",
        },
        {
            label: "Alchemy Items",
            value: "info.alchemy-items.alchemy-items-table",
        },
        {
            label: "Alchemy Holy Items",
            value: "info.alchemy-items.alchemy-holy-items-table",
        },
        {
            label: "Alchemy Kingdom Damaging Items",
            value: "info.alchemy-items.alchemy-kingdom-items-table",
        },
        {
            label: "Skills",
            value: "admin.skills.skills-table",
        },
        {
            label: "Class Skills",
            value: "info.skills.class-skills",
        },
        {
            label: "Maps",
            value: "admin.maps.maps-table",
        },
        {
            label: "NPCs",
            value: "admin.npcs.npc-table",
        },
        {
            label: "Kingdom Passive Skills",
            value: "admin.passive-skills.passive-skill-table",
        },
        {
            label: "Kingdom Building",
            value: "admin.kingdoms.buildings.buildings-table",
        },
        {
            label: "Kingdom Units",
            value: "admin.kingdoms.units.units-table",
        },
        {
            label: "Regular Locations",
            value: "info.locations.regular-locations",
        },
        {
            label: "Special Locations",
            value: "info.locations.special-locations",
        },
        {
            label: "Weekly Fight Locations",
            value: "info.locations.weekly-fight-locations",
        },
        {
            label: "Class Specials",
            value: "admin.class-specials.class-specials-table",
        },
        {
            label: "Raids",
            value: "admin.raids.raids-table",
        },
    ];
};
