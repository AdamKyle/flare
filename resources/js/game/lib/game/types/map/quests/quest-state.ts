import {QuestDetails} from "../../../map/types/quest-details";

export default interface QuestState {

    quests: QuestDetails[] | [];

    raid_quests: QuestDetails[]|[];

    completed_quests: number[] | [];

    loading: boolean,

    current_plane: string;
}
