import KingdomDetails from "../kingdom-details";
import KingdomLogDetails from "../kingdom-log-details";

export default interface KingdomListProps {

    my_kingdoms: KingdomDetails[] | [];

    logs: KingdomLogDetails[] | [];

    view_port: number;

    character_gold: number;

    user_id: number;

    is_dead: boolean;
}
