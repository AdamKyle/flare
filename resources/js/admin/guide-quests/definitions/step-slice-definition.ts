import { GuideQuestContentBlockDefinition } from '../api/definitions/guide-quest-definition';

export default interface StepSliceDefinition {
  [key: string]: unknown;
  intro_text?: GuideQuestContentBlockDefinition[] | null;
  desktop_instructions?: GuideQuestContentBlockDefinition[] | null;
  mobile_instructions?: GuideQuestContentBlockDefinition[] | null;
}
