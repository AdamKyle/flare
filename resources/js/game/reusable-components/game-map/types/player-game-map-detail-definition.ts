import GameMapFactualDefinition from './game-map-factual-definition';
import AreaGemContextDefinition from '../../gems/api/definitions/area-gem-context-definition';

export default interface PlayerGameMapDetailDefinition extends GameMapFactualDefinition {
  gem_context: AreaGemContextDefinition | null;
}
