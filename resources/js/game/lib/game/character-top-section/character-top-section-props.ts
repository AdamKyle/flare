export default interface CharacterTopSectionProps {

    character_id: number;

    view_port: number;

    update_character_status: (characterStatus: {is_dead: boolean, can_adventure: boolean}) => void

    update_character_currencies: (currencies: {gold: number, shards: number, gold_dust: number, copper_coins: number}) => void
}
