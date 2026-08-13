import SetSailPortDefinition from '../../api/definitions/set-sail-port-definition';

export default interface SetSailSectionProps {
  character_gold: number;
  selected_port: SetSailPortDefinition;
  on_set_sail: () => void;
  is_submitting: boolean;
}
