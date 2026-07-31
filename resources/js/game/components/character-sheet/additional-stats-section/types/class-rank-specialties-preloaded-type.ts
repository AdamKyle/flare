import ClassSpecialtiesType from "./sections/class-ranks/deffinitions/class-specialties-type";
import CharacterSpecialsEquippedTyp from "./sections/class-ranks/deffinitions/character-specials-equipped-typ";
import ClassRankType from "./sections/class-ranks/deffinitions/class-rank-type";

export default interface ClassRankSpecialtiesPreloadedType {
    class_specialties: ClassSpecialtiesType[];
    specials_equipped: CharacterSpecialsEquippedTyp[];
    class_ranks: ClassRankType[];
    other_class_specials: CharacterSpecialsEquippedTyp[];
}
