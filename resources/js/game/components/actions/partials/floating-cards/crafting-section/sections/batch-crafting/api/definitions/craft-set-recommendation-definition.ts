import CraftSetRecommendationPositionDefinition from './craft-set-recommendation-position-definition';
import { CraftSetPosition } from '../../enums/craft-set-position';

export default interface CraftSetRecommendationDefinition {
  positions: CraftSetRecommendationPositionDefinition[];
  missing_positions: CraftSetPosition[];
}
