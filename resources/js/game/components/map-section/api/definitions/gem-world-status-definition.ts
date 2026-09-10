import GemWorldEntryDefinition from './gem-world-entry-definition';
import GemWorldExitDefinition from './gem-world-exit-definition';
import AreaGemContextDefinition from '../../../../reusable-components/gems/api/definitions/area-gem-context-definition';

export default interface GemWorldStatusDefinition {
  inside_gem_world: boolean;
  current_context: AreaGemContextDefinition | null;
  entry: GemWorldEntryDefinition | null;
  exit: GemWorldExitDefinition | null;
}
