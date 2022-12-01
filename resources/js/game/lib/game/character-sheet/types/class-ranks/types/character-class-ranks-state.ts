import ClassRankType from "../class-rank-type";
import GameClassType from "../game-class-type";

export default interface CharacterClassRanksState {

    class_ranks: ClassRankType[]|[];

    dark_tables: boolean;

    loading: boolean;

    open_class_details: boolean;

    class_name_selected: ClassRankType|null;
}
