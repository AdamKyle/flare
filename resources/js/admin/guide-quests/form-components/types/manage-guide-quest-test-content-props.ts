import GuideQuestDefinition from '../../api/definitions/guide-quest-definition';

export default interface ManageGuideQuestsTextContentProps {
  step: number;
  field_key: 'intro_text' | 'desktop_instructions' | 'mobile_instructions';
  on_update_content: (
    step: number,
    data: Partial<GuideQuestDefinition>
  ) => void;
  initial_content: Partial<GuideQuestDefinition>;
}
