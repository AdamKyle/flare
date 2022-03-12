import {QuestDetails} from "../../../map/types/quest-details";

export default interface QuestState {

    quests: QuestDetails[] | [];

    completed_quests: {quest_id: number}[] | [];

    loading: boolean,
}
