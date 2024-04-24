import { QuestDetails } from "../../quest-details";

export default interface QuestState {
    quests: QuestDetails[] | [];

    raid_quests: QuestDetails[] | [];

    is_winter_event: boolean;

    completed_quests: number[] | [];

    loading: boolean;

    current_plane: string;
}
