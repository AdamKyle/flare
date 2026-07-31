import InventoryDetails from "../../../../../game/lib/game/character-sheet/types/inventory/inventory-details";
import SkillType from "../../../../../game/lib/game/character-sheet/types/skills/skill-type";
import KingdomPassiveRow from "../../../../../game/lib/game/character-sheet/types/skills/kingdom-passive-row";
import ClassRankType from "../../../../../game/components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/class-rank-type";
import ClassRankOfferedType from "../../../../../game/components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/class-rank-offered-type";
import ClassRankSpecialtiesPreloadedType from "../../../../../game/components/character-sheet/additional-stats-section/types/class-rank-specialties-preloaded-type";
import TopsValue from "../../shared/types/tops-value";
import { ChartPayload } from "../components/profile/sheet-inspect/tops-chart-card";

export interface CharacterOverview extends Record<string, TopsValue> {
    id: number;
    name: string;
    level: number;
}

export interface CharacterSkillProfile {
    regular_skills: SkillType[];
    crafting_skills: SkillType[];
}

export interface EquipmentProfile {
    source: string;
    set_name: string | null;
    items: InventoryDetails[];
}

export interface PublicQuestRow extends Record<string, TopsValue> {
    id: number;
    name: string;
}

export interface QuestProfile {
    summary_chart: ChartPayload;
    completion_chart: ChartPayload;
    completed_quests: PublicQuestRow[];
    completed_guide_quests: PublicQuestRow[];
}

export interface KingdomProfile {
    kingdoms: Record<string, TopsValue>[];
}

export interface AnalyticsProfile {
    analytics_kills_chart: ChartPayload;
    analytics_runs_chart: ChartPayload;
}

export interface ActivityProfile {
    login_count_chart: ChartPayload;
    login_duration_chart: ChartPayload;
}

export default interface CharacterProfile {
    overview: CharacterOverview;
    summary: Record<string, TopsValue>;
    info: Record<string, TopsValue>;
    stats: Record<string, TopsValue>;
    equipment: EquipmentProfile;
    skills: CharacterSkillProfile;
    kingdom_passives: KingdomPassiveRow[];
    class_ranks: ClassRankType[];
    class_ranks_offered: ClassRankOfferedType[];
    class_rank_specialties: ClassRankSpecialtiesPreloadedType;
    factions: Record<string, TopsValue>;
    reincarnation: Record<string, TopsValue>;
    activity: ActivityProfile;
    quests: QuestProfile;
    kingdoms: KingdomProfile;
    analytics: AnalyticsProfile;
}
