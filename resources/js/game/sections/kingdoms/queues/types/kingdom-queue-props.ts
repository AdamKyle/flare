import KingdomDetails from "../../../../lib/game/kingdoms/kingdom-details";


export interface KingdomQueueProps {
    user_id: number;
    character_id: number;
    kingdom_id: number;
    kingdoms: KingdomDetails[]|[];
}
