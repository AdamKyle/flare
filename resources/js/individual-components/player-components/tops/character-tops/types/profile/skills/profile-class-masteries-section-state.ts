import TopsValue from "../../../../shared/types/tops-value";

export default interface ProfileClassMasteriesSectionState {
    selectedClass: Record<string, TopsValue> | null;
    selectedSpecialty: Record<string, TopsValue> | null;
}
