import { RolledGemDisplayGroup } from './rolled-gem-display-group';
import RolledGemDefinition from '../api/definitions/rolled-gem-definition';

export default interface RolledGemStatsProps {
  roll: RolledGemDefinition;
  display_groups: RolledGemDisplayGroup[];
}
