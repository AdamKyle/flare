import { GuideQuestContentBlockDefinition } from '../../api/definitions/guide-quest-definition';
import GuideQuestFormStateDefinition from '../../definitions/guide-quest-form-state-definition';

export default interface ManageGuideQuestTextContentProps {
  field_key: 'intro_text' | 'desktop_instructions' | 'mobile_instructions';
  value: GuideQuestContentBlockDefinition[];
  on_change: (
    field: keyof GuideQuestFormStateDefinition,
    blocks: GuideQuestContentBlockDefinition[]
  ) => void;
}
