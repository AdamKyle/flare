import TopsValue from "../../../../shared/types/tops-value";

export default interface ProfileStatsSectionState {
    selectedStat: Record<string, TopsValue> | null;
}
