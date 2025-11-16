import GuideQuestResponseDefinition from '../../definitions/guide-quest-response-defintion';

export default interface UseStoreGuideQuestContentParams {
  update_guide_quest: (data: GuideQuestResponseDefinition) => void;
}
