import SeerGemRemovalItemDefinition from './seer-gem-removal-item-definition';
import SeerItemDefinition from './seer-item-definition';

export default interface SeerGemRemovalDataDefinition {
  items: SeerItemDefinition[];
  gems: SeerGemRemovalItemDefinition[];
}
