import QuestFormStateDefinition from './quest-form-state-definition';

type QuestFormErrorsDefinition = Partial<
  Record<keyof QuestFormStateDefinition, string>
>;

export default QuestFormErrorsDefinition;
