import ClassFormStateDefinition from './class-form-state-definition';

type ClassFormErrorsDefinition = Partial<
  Record<keyof ClassFormStateDefinition, string>
>;

export default ClassFormErrorsDefinition;
