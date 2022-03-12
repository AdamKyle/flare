import {QuestDetails} from "../../../map/types/quest-details";

export default interface QuestTreeProps {

    quests: QuestDetails[] | [];

    character_id: number;
}
