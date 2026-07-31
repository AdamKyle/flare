export default interface KingdomPassiveRow {
    id: number;

    name: string;

    description: string | null;

    current_level: number;

    max_level: number;

    parent_skill_id: number | null;

    hours_to_next: number;

    is_locked: boolean;

    quest_name: string | null;

    is_quest_complete: boolean;

    passive_skill: {
        description: string | null;
        unlocks_at_level: number | null;
    };

    children: KingdomPassiveRow[];

    started_at?: string | null;

    completed_at?: string | null;
}
