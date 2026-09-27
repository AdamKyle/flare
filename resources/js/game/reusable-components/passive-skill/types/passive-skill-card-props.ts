export default interface PassiveSkillCardProps {
  passive_skill_id: number;
  name: string;
  effect_label: string;
  unlocks_at_level?: number | null;
  max_level?: number | null;
  on_open_passive_skill?: (passiveSkillId: number) => void;
}
