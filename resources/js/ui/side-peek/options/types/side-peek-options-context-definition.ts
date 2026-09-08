import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

export default interface SidePeekOptionsContextDefinition {
  set_options: (options: SidePeekOptionDefinition[]) => void;
  clear_options: () => void;
}
