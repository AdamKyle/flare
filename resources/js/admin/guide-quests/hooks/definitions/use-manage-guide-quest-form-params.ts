import GuideQuestResponseDefinition from '../../api/definitions/guide-quest-response-defintion';

export default interface UseManageGuideQuestFormParams {
  guide_quest_id: number;
  data: GuideQuestResponseDefinition | null;
  updateGuideQuest: (data: GuideQuestResponseDefinition) => void;
}
