import GuideQuestResponseDefinition from '../../api/definitions/guide-quest-response-definition';
import GuideQuestFormErrorsDefinition from '../../definitions/guide-quest-form-errors-definition';
import GuideQuestFormStateDefinition from '../../definitions/guide-quest-form-state-definition';

export default interface GuideQuestFormFieldsProps {
  state: GuideQuestFormStateDefinition;
  errors: GuideQuestFormErrorsDefinition;
  form_options: GuideQuestResponseDefinition;
  on_change: <K extends keyof GuideQuestFormStateDefinition>(
    field: K,
    value: GuideQuestFormStateDefinition[K]
  ) => void;
}
