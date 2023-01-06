import ClassSpecialtiesType from "../class-specialties-type";
import CharacterSpecialsEquippedTyp from "../character-specials-equipped-typ";
import ClassRankType from "../class-rank-type";

export default interface ClassSpecialtiesState {

    loading: boolean;

    class_specialties: ClassSpecialtiesType[]|[];

    class_specials_for_table: ClassSpecialtiesType[]|[];

    special_selected: ClassSpecialtiesType | null;

    equipped_special: CharacterSpecialsEquippedTyp | null;

    specialties_equipped: CharacterSpecialsEquippedTyp[]|[];

    other_class_specialties: CharacterSpecialsEquippedTyp[]|[];

    class_ranks: ClassRankType[]|[];

    dark_tables: boolean;

    equipping: boolean;

    success_message: string | null;

    error_message: string | null;

    selected_filter: string | null;

    show_equipped: boolean,
}
