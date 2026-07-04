export default interface BatchCraftingStatusDisplayState {
    page: number;
    itemPages: Record<string, number>;
    openSlotId: number | null;
    openSnapshot: any | null;
}
