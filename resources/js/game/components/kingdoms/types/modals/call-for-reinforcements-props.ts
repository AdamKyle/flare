export default interface CallForReinforcementsProps {
    kingdom_id: number;

    character_id: number;

    is_open: boolean;

    handle_close: () => void;
}
