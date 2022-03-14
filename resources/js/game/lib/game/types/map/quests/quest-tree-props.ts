import {QuestDetails} from "../../../map/types/quest-details";

export default interface QuestTreeProps {

    quests: QuestDetails[] | [];

    completed_quests: number[] | [];

    character_id: number;

    plane: string;
}
