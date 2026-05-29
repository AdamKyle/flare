import QuestType from "../../../../../lib/game/types/quests/quest-type";

export default interface QuestsProps {
    quest_details: QuestType;

    character_id: number;

    update_quests: (quests: QuestType) => void;

    is_automation_running: boolean;

    is_faction_loyalty_automation_running: boolean;

    is_delve_running: boolean;
}
