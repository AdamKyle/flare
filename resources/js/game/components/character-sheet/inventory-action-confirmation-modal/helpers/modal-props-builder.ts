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
            case InventoryActionConfirmationType.DISENCHANT_ALL:
                return "Disenchant All";
            case InventoryActionConfirmationType.DISENCHANT_SELECTED:
                return "Disenchant Selected";
            case InventoryActionConfirmationType.MOVE_SELECTED:
                return "Move Selected";
            case InventoryActionConfirmationType.EQUIP_SELECTED:
                return "Equip Selected";
            case InventoryActionConfirmationType.DESTROY_ALL_ALCHEMY_ITEMS:
                return "Destroy All Alchemy Items";
            case InventoryActionConfirmationType.SELL_SELECTED_FROM_SET:
                return "Sell Selected";
            case InventoryActionConfirmationType.DISENCHANT_SELECTED_FROM_SET:
                return "Disenchant Selected";
            case InventoryActionConfirmationType.DESTROY_SELECTED_FROM_SET:
                return "Destroy Selected Items";
            case InventoryActionConfirmationType.DESTROY_ALL_FROM_SET:
                return "Destroy All";
            case InventoryActionConfirmationType.SELL_ALL_FROM_SET:
                return "Sell All";
            case InventoryActionConfirmationType.DISENCHANT_ALL_FROM_SET:
                return "Disenchant All";
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
            case InventoryActionConfirmationType.DISENCHANT_ALL:
                return "character/" + characterId + "/inventory/disenchant-all";
            case InventoryActionConfirmationType.DISENCHANT_SELECTED:
                return (
                    "character/" +
                    characterId +
                    "/inventory/disenchant-selected"
                );
            case InventoryActionConfirmationType.MOVE_SELECTED:
                return "character/" + characterId + "/inventory/move-selected";
            case InventoryActionConfirmationType.EQUIP_SELECTED:
                return "character/" + characterId + "/inventory/equip-selected";
            case InventoryActionConfirmationType.DESTROY_ALL_ALCHEMY_ITEMS:
                return (
                    "character/" +
                    characterId +
                    "/inventory/destroy-all-alchemy-items"
                );
            case InventoryActionConfirmationType.SELL_SELECTED_FROM_SET:
                return (
                    "character/" + characterId + "/inventory-set/sell-selected"
                );
            case InventoryActionConfirmationType.DISENCHANT_SELECTED_FROM_SET:
                return (
                    "character/" +
                    characterId +
                    "/inventory-set/disenchant-selected"
                );
            case InventoryActionConfirmationType.DESTROY_SELECTED_FROM_SET:
                return (
                    "character/" +
                    characterId +
                    "/inventory-set/destroy-selected"
                );
            case InventoryActionConfirmationType.DESTROY_ALL_FROM_SET:
                return (
                    "character/" + characterId + "/inventory-set/destroy-all"
                );
            case InventoryActionConfirmationType.SELL_ALL_FROM_SET:
                return "character/" + characterId + "/inventory-set/sell-all";
            case InventoryActionConfirmationType.DISENCHANT_ALL_FROM_SET:
                return (
                    "character/" + characterId + "/inventory-set/disenchant-all"
                );
            default:
                return "";
        }
    }
}
