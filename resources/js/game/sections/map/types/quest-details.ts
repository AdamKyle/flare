import { ChildQuestDetails } from "./child-quest-details";

export interface QuestDetails {
    id: number;

    parent_id: number;

    parent_quest_id: number;

    is_parent: boolean;

    name: string;

    belongs_to_map_name: string;

    required_quest_id: number | null;

    child_quests: ChildQuestDetails[] | [];

    required_quest_chain_details: {
        quest_ids: number[];
        quest_names: string[];
        map_name: string;
        starts_with_npc: string;
    } | null;
}
