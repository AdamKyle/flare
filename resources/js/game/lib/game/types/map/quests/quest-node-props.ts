import {QuestDetails} from "../../../map/types/quest-details";
import {ChildQuestDetails} from "../../../map/types/child-quest-details";
import QuestType from "../../quests/quest-type";

export default interface QuestNodeProps {

    quest: QuestDetails | ChildQuestDetails | null;

    completed_quests: number[] | [];

    character_id: number;

    update_quests: (quests: QuestType) => void;
}
