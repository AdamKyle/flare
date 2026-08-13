import SetSailCurrentPortDefinition from './set-sail-current-port-definition';
import SetSailPortDefinition from './set-sail-port-definition';

export default interface FetchSetSailPortsResponseDefinition {
  current_port: SetSailCurrentPortDefinition;
  port_list: SetSailPortDefinition[];
}
