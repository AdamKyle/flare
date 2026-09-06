import ClassMasteryFormStateDefinition from './class-mastery-form-state-definition';

type ClassMasteryFormErrorsDefinition = Partial<
  Record<keyof ClassMasteryFormStateDefinition, string>
>;

export default ClassMasteryFormErrorsDefinition;
