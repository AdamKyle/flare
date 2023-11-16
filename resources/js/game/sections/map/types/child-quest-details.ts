export interface ChildQuestDetails {

    id: number

    parent_id: number;

    is_parent: boolean;

    name: string;

    belongs_to_map_name: string;

    child_quests: ChildQuestDetails[];
}
