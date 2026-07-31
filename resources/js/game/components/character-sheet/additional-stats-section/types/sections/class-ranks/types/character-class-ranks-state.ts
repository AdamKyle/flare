import ClassRankType from "../deffinitions/class-rank-type";

export default interface CharacterClassRanksState {
    class_ranks: ClassRankType[] | [];

    dark_tables: boolean;

    loading: boolean;

    open_class_details: boolean;

    class_name_selected: ClassRankType | null;

    show_class_specialties: boolean;

    switching_class: boolean;

    success_message: string | null;

    error_message: string | null;

    mastery_class_id: number | null;

    selected_mastery: {
        id: number;
        mastery_name: string;
        weapon_type: string;
        level: number;
        current_xp: number;
        required_xp: number;
        is_leveled: boolean;
        associated_class: string;
    } | null;

    mastery_details_visible: boolean;

    pending_mastery: CharacterClassRanksState["selected_mastery"];
}
