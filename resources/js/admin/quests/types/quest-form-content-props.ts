import QuestFormDefinition from '../api/definitions/quest-form-definition';

export default interface QuestFormContentProps {
  quest_id: number | null;
  parent_quest_id: number | null;
  on_saved: (quest: QuestFormDefinition) => void;
  on_cancel: () => void;
  embedded: boolean;
}
