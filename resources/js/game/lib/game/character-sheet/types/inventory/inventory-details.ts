import ItemSkill from "../../../../../sections/character-sheet/components/item-skill-management/types/deffinitions/item-skill";
import DataTableBaseData from "../../../../ui/types/tables/data-table-base-data";
import ItemSkillProgression from "../../../../../sections/character-sheet/components/item-skill-management/types/deffinitions/item-skill-progression";

export default interface InventoryDetails extends DataTableBaseData {
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

    has_holy_stacks_applied: number;

    usable: boolean;

    item_skills: ItemSkill[] | [];

    item_skill_progressions: ItemSkillProgression[] | [];
}
