export default interface NpcKingdomProps {

    kingdoms: {id: number, x_position: number, y_position: number, npc_owned: boolean}[] | null;

    character_id: number;

    character_position: {x: number, y: number};

    currencies?: {gold: number, gold_dust: number, shards: number, copper_coins: number};

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void;

    can_move: boolean;
}
