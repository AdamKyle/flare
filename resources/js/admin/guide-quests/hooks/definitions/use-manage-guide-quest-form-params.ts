import GuideQuestResponseDefinition from '../../api/definitions/guide-quest-response-definition';

export default interface UseManageGuideQuestFormParams {
  guide_quest_id: number;
  data: GuideQuestResponseDefinition;
  updateGuideQuest: (data: GuideQuestResponseDefinition) => void;
}
