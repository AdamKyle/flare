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
            default:
                return "ERROR";
        }
    }

    public fetchActionUrl(characterId: number): string {
        switch (this.inventoryActionType) {
            case InventoryActionConfirmationType.DESTROY_ALL:
                return "character/" + characterId + "/inventory/destroy-all";
            default:
                return "";
        }
    }
}
