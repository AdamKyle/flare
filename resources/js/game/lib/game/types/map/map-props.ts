export default interface MapProps {

    user_id: number,

    character_id: number,

    view_port: number,

    currencies?: {
        gold: number,
        shards: number,
        gold_dust: number,
        copper_coins: number,
    };
}
