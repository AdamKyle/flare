import TopsValue from "../../../../shared/types/tops-value";

export default interface ProfileWornItemsState {
    selectedItem: Record<string, TopsValue> | null;
}
