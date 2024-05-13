import { InventoryActionConfirmationType } from "./enums/inventory-action-confirmation-type";

export default class ModalPropsBuilder {
    private inventoryActionType: InventoryActionConfirmationType | null;

    constructor() {
        this.inventoryActionType = null;
    }

    public setActionType(
        actionType: InventoryActionConfirmationType,
    ): ModalPropsBuilder {
        this.inventoryActionType = actionType;

        return this;
    }

    public fetchModalName(): string {
        switch (this.inventoryActionType) {
            case InventoryActionConfirmationType.DESTROY_ALL:
                return "Destroy All";
            case InventoryActionConfirmationType.DESTROY_SELECTED:
                return "Destroy Selected Items";
            case InventoryActionConfirmationType.SELL_ALL:
                return "Sell All";
            case InventoryActionConfirmationType.SELL_SELECTED:
                return "Sell Selected";
            default:
                return "ERROR";
        }
    }

    public fetchActionUrl(characterId: number): string {
        switch (this.inventoryActionType) {
            case InventoryActionConfirmationType.DESTROY_ALL:
                return "character/" + characterId + "/inventory/destroy-all";
            case InventoryActionConfirmationType.DESTROY_SELECTED:
                return (
                    "character/" + characterId + "/inventory/destroy-selected"
                );
            case InventoryActionConfirmationType.SELL_ALL:
                return "character/" + characterId + "/inventory/sell-all";
            case InventoryActionConfirmationType.SELL_SELECTED:
                return "character/" + characterId + "/inventory/sell-selected";
            default:
                return "";
        }
    }
}
