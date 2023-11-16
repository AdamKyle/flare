import {QuestDetails} from "../../quest-details";
import QuestType from "../../../../../lib/game/types/quests/quest-type";

export default interface QuestTreeProps {

    quests: QuestDetails[] | [];

    raid_quests: QuestDetails[]|[];

    completed_quests: number[] | [];

    character_id: number;

    plane: string;

    update_quests: (quests: QuestType) => void;
}
