import Inventory from "./inventory/inventory";
import ItemSkill from "../../../../../game/sections/character-sheet/components/item-skill-management/types/deffinitions/item-skill"
import ItemSkillProgressions from "../../../../../game/sections/character-sheet/components/item-skill-management/types/deffinitions/item-skill-progression"


export default interface CharacterInventoryTabsState {
    table: string;

    dark_tables: boolean;

    loading: boolean;

    inventory: Inventory | null;

    disable_tabs: boolean;

    item_skill_data: {
        slot_id: number;
        item_skills: ItemSkill[];
        item_skill_progressions: ItemSkillProgressions[];
    }|null
}

