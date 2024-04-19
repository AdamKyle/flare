import ClassSpecialtiesType from "../deffinitions/class-specialties-type";
import CharacterSpecialsEquippedTyp from "../deffinitions/character-specials-equipped-typ";
import ClassRankType from "../deffinitions/class-rank-type";

export default interface ClassSpecialtiesState {

    loading: boolean;

    class_specialties: ClassSpecialtiesType[]|[];

    class_specials_for_table: ClassSpecialtiesType[]|[];

    filtered_other_class_specialties: CharacterSpecialsEquippedTyp[]|[];

    special_selected: ClassSpecialtiesType | null;

    equipped_special: CharacterSpecialsEquippedTyp | null;

    specialties_equipped: CharacterSpecialsEquippedTyp[]|[];

    other_class_specialties: CharacterSpecialsEquippedTyp[]|[];

    original_class_specialties: CharacterSpecialsEquippedTyp[]|[];

    class_ranks: ClassRankType[]|[];

    dark_tables: boolean;

    equipping: boolean;

    equipping_special_id: number | null;

    success_message: string | null;

    error_message: string | null;

    selected_filter: string | null;

    other_selected_filter: string | null;

    show_equipped: boolean,

}
