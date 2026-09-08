export default interface CharacterQuestDetailViewProps {
  character_id: number;
  quest_id: number;
  has_back: boolean;
  completed_quest_ids: number[];
  on_back: () => void;
  on_close: () => void;
  on_open_quest: (id: number) => void;
  on_completed_quests_change: (ids: number[]) => void;
}
