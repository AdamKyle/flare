import ClassSpecialtiesType from "../class-specialties-type";
import CharacterSpecialsEquippedTyp from "../character-specials-equipped-typ";

export default interface ClassSpecialtiesState {

    loading: boolean;

    class_specialties: ClassSpecialtiesType[]|[];

    special_selected: ClassSpecialtiesType | null;

    equipped_special: CharacterSpecialsEquippedTyp | null;

    specialties_equipped: CharacterSpecialsEquippedTyp[]|[];

    dark_tables: boolean;

    equipping: boolean;

    success_message: string | null;

    error_message: string | null;
}
