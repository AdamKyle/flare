import {QuestDetails} from "../../map/types/quest-details";

export default interface QuestType {

    quests: QuestDetails[]|[];

    completed_quests: number[];

    player_plane: string;
}
