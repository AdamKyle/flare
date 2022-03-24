import Inventory from "./inventory/inventory";

export default interface CharacterInventoryTabsState {
    table: string;

    dark_tables: boolean;

    loading: boolean;

    inventory: Inventory | null;
}
