import ClassSpecialtiesType from "../class-specialties-type";

export default interface ClassSpecialtiesState {

    loading: boolean;

    class_specialties: ClassSpecialtiesType[]|[];

    special_selected: ClassSpecialtiesType | null;

    specialties_equipped: any[]|[];

    dark_tables: boolean;
}
