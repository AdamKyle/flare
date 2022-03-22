import {QuestDetails} from "../../../map/types/quest-details";

export default interface QuestsProps {

    quest_details: {
        quests: QuestDetails[]|[],
        completed_quests: number[],
        player_plane: string,
    }

    character_id: number;
}
