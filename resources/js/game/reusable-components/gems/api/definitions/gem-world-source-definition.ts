import AreaGemSourceDefinition from './area-gem-source-definition';
import RolledGemDefinition from './rolled-gem-definition';

export default interface GemWorldSourceDefinition extends AreaGemSourceDefinition {
  rolled_gem: RolledGemDefinition;
}
