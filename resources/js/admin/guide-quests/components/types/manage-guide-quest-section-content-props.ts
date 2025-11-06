import GuideQuestDefinition from '../../api/definitions/guide-quest-definition';

export default interface UseFetchGuideQuestsDefinition {
  step: number;
  on_update_content: (
    step: number,
    data: Partial<GuideQuestDefinition>
  ) => void;
}
