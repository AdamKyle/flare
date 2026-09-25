import SetOptionDefinition from '../../definitions/set-options-definition';

export default interface SetStatusNoticesProps {
  selected_set: SetOptionDefinition;
  restriction_message: string | null;
  show_restriction: boolean;
  is_violating_set_rules: boolean;
}
