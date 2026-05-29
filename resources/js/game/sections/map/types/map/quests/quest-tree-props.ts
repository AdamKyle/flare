import { QuestDetails } from "../../quest-details";
import QuestType from "../../../../../lib/game/types/quests/quest-type";

export default interface QuestTreeProps {
    quests: QuestDetails[] | [];

    raid_quests: QuestDetails[] | [];

    completed_quests: number[] | [];

    character_id: number;

    plane: string;

    update_quests: (quests: QuestType) => void;

    is_automation_running: boolean;

    is_faction_loyalty_automation_running: boolean;

    is_delve_running: boolean;
}
