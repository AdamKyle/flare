import QuestType from "../../../../../lib/game/types/quests/quest-type";

export default interface QuestsProps {
    quest_details: QuestType;

    character_id: number;

    update_quests: (quests: QuestType) => void;
}
