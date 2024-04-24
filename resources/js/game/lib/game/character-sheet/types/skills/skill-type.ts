import DataTableBaseData from "../../../../ui/types/tables/data-table-base-data";

export default interface SkillType extends DataTableBaseData {
    id: number;

    character_id: number;

    can_train: boolean;

    is_locked: boolean;

    is_training: boolean;

    level: number;

    max_level: number;

    name: string;

    skill_bonus: number;

    skill_type: string;

    xp: number;

    xp_max: number;

    xp_towards: number | null;

    is_class_skill: boolean;
}
