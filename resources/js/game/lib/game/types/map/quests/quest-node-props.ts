import {QuestDetails} from "../../../map/types/quest-details";
import {ChildQuestDetails} from "../../../map/types/child-quest-details";

export default interface QuestNodeProps {

    quest: QuestDetails | ChildQuestDetails | null;

    completed_quests: number[] | [];

    character_id: number;
}
