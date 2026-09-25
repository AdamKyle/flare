import SetOptionDefinition from '../definitions/set-options-definition';

export default interface SetChoicesProps {
  character_id: number;
  on_set_change: (selectedSet: SetOptionDefinition) => void;
  on_set_selection_clear: () => void;
  on_preselected_set_resolved?: (preselectedSet: SetOptionDefinition) => void;
  set_equipped_set_name?: boolean;
  dont_show_equipped_set?: boolean;
  initial_set_id?: number;
  initial_set_name?: string;
}
