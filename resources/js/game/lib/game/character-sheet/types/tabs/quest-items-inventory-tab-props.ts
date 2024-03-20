import InventoryDetails from "../inventory/inventory-details";

export default interface QuestItemsInventoryTabProps {
    dark_table: boolean;

    quest_items: InventoryDetails[] | [];

    is_dead: boolean;

    character_id: number;

    view_port: number;
}
