import { QuestDetails } from "../../quest-details";
import { ChildQuestDetails } from "../../child-quest-details";
import QuestType from "../../../../../lib/game/types/quests/quest-type";

export default interface QuestNodeProps {
    quest: QuestDetails | ChildQuestDetails | null;

    completed_quests: number[] | [];

    character_id: number;

    update_quests: (quests: QuestType) => void;

    is_automation_running: boolean;

    is_faction_loyalty_automation_running: boolean;

    is_delve_running: boolean;
}
