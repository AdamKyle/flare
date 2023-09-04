export default interface CraftingActionButtonsProps {
    craft: () => void;
    change_type: () => void;
    clear_crafting: () => void;
    can_craft: boolean;
    can_close: boolean;
    can_change_type: boolean
}
