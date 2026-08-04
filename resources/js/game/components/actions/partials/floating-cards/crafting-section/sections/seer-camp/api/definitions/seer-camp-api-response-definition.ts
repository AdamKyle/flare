import SeerCampCostsDefinition from './seer-camp-costs-definition';
import SeerGemDefinition from './seer-gem-definition';
import SeerGemRemovalDataDefinition from './seer-gem-removal-data-definition';
import SeerItemDefinition from './seer-item-definition';
export default interface SeerCampApiResponseDefinition {
  items: SeerItemDefinition[];
  gems: SeerGemDefinition[];
  costs: SeerCampCostsDefinition;
  message?: string;
  removal_data?: SeerGemRemovalDataDefinition;
}
