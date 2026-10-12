import GuideQuestDefinition from '../../api/definitions/guide-quest-definition';

export default interface GuideQuestSelectorProps {
  quests: GuideQuestDefinition[];
  selected_quest_id: number;
  on_select: (questId: number) => void;
}
