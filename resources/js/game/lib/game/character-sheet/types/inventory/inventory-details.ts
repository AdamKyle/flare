import ItemSkill from "../../../../../sections/character-sheet/components/item-skill-management/types/deffinitions/item-skill";
import DataTableBaseData from "../../../../ui/types/tables/data-table-base-data";
import ItemSkillProgression from "../../../../../sections/character-sheet/components/item-skill-management/types/deffinitions/item-skill-progression";
import { ItemDefinition } from "../../../../../sections/character-sheet/components/modals/components/deffinitions/item-definition";

export default interface InventoryDetails
    extends DataTableBaseData,
        ItemDefinition {
    attached_affixes_count: number;

    id: number;

    slot_id: number;

    item_id: number;

    is_unique: boolean;

    is_mythic: boolean;

    is_cosmic: boolean;

    item_name: string;

    description: string;

    type: string;

    ac: number;

    attack: number;

    healing: number;

    position?: string | null;

    base_damage: number;

    base_ac: number;

    base_healing: number;

    has_holy_stacks_applied: number;

    usable: boolean;

    item_skills: ItemSkill[] | [];

    item_skill_progressions: ItemSkillProgression[] | [];
}
