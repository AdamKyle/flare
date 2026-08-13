import SetSailPortDefinition from '../../api/definitions/set-sail-port-definition';

export default interface PortLocationsDropDownProps {
  ports: SetSailPortDefinition[];
  on_select: (port: SetSailPortDefinition) => void;
  on_clear: () => void;
  aria_labelled_by?: string;
}
