import CraftSetHandRecommendationItemDefinition from '../../definitions/craft-set-hand-recommendation-item-definition';

export default interface UseCraftSetHandRecommendationDefinition {
  recommendation: CraftSetHandRecommendationItemDefinition | null;
  loading: boolean;
  error: string | null;
}
