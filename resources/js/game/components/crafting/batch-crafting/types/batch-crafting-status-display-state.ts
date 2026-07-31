export default interface BatchCraftingStatusDisplayState {
    page: number;
    openItemId: number | null;
    openSetSlotId: number | null;
    openAlchemySlotId: number | null;
    openSnapshot: any | null;
    affixDetailsModalAffix: any | null;
    affixDetailsModalOpen: boolean;
}
