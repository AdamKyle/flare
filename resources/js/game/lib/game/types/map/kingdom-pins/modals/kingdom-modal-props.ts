export default interface KingdomModalProps {
    is_open: boolean;

    handle_close: () => void;

    kingdom_id: number;

    character_id: number;

    character_position?: {x: number, y: number};

    currencies?: {gold: number, gold_dust: number, shards: number, copper_coins: number};

    teleport_player?: (data: {x: number, y: number, cost: number, timeout: number}) => void;

    hide_secondary: boolean;
}
