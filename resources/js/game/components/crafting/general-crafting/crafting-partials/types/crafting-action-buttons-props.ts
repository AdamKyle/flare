export default interface CraftingActionButtonsProps {
    craft: (craftForNpc: boolean, craftForEvent: boolean) => void;
    change_type: () => void;
    clear_crafting: () => void;
    can_craft: boolean;
    can_close: boolean;
    can_change_type: boolean;
    show_craft_for_npc: boolean;
    show_craft_for_event: boolean;
}
