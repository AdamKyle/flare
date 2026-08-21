import CraftSetRecommendationDefinition from '../../definitions/craft-set-recommendation-definition';

export default interface UseCraftSetRecommendationDefinition {
  recommendation: CraftSetRecommendationDefinition | null;
  loading: boolean;
  error: string | null;
}
