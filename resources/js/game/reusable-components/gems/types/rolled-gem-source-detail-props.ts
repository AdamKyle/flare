import { RolledGemDisplayGroup } from './rolled-gem-display-group';
import GemWorldSourceDefinition from '../api/definitions/gem-world-source-definition';

export default interface RolledGemSourceDetailProps {
  source: GemWorldSourceDefinition;
  display_groups: RolledGemDisplayGroup[];
}
