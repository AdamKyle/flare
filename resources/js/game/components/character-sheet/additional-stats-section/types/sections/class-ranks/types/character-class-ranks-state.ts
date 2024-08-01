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
}
