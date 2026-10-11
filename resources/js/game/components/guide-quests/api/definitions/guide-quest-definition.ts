export interface GuideQuestContentBlockDefinition {
  id: string;
  content: string;
  image_url: string | null;
}

export default interface GuideQuestDefinition {
  id: number;
  name: string;
  intro_text: GuideQuestContentBlockDefinition[] | null;
  instructions: string | null;
  desktop_instructions: GuideQuestContentBlockDefinition[] | null;
  mobile_instructions: GuideQuestContentBlockDefinition[] | null;
  required_level: number | null;
  required_training_skill: number | null;
  training_skill_name: string | null;
  required_training_xp_sacrifice: number | null;
  required_skill: number | null;
  skill_name: string | null;
  required_skill_level: number | null;
  required_faction_id: number | null;
  faction_name: string | null;
  required_faction_level: number | null;
  faction_points_per_kill: number | null;
  gold_reward: number | null;
  gold_dust_reward: number | null;
  shards_reward: number | null;
  xp_reward: number | null;
  has_walkthrough: boolean;
  walkthrough_status: 'not_started' | 'in_progress' | 'completed';
}
