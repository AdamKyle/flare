import BuildingDetails from "../../building-details";

export default interface GoblinBankModalProps {

    is_open: boolean;

    handle_close: () => void;

    character_gold: number;

    gold_bars: number;

    kingdom_id: number;

    goblin_bank: BuildingDetails;
}
