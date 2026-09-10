export default interface QuestDetailScreenProps {
  character_id: number;
  quest_id: number;
  completed_quest_ids: number[];
  on_completed_quests_change: (ids: number[]) => void;
}
