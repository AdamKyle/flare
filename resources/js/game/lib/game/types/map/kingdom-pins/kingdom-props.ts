export default interface KingdomProps {

    kingdoms: {id: number, x_position: number, y_position: number, color: string, character_id: number}[] | null;

    character_id: number;

    character_position: {x: number, y: number};

    currencies: {gold: number, gold_dust: number, shards: number, copper_coins: number} | null;

    teleport_player: (data: {x: number, y: number, cost: number, timeout: number}) => void;
}
