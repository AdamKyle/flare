
export interface LabyrinthOracleInventory {
    id: number;
    affix_name: string;
}

export default interface LabyrinthOracleState {
    [key: string]: any;
    loading: boolean;
    transferring: boolean;
    item_to_transfer_from: number | null;
    item_to_transfer_to: number | null;
    inventory: LabyrinthOracleInventory[]|[]
    error_message: string | null,
    success_message: string | null,
}
