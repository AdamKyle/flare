import MonsterDefinition from '../../../../../../api-definitions/monsters/monster-definition';

export default interface CelestialStatsResponseDefinition {
  monster: MonsterDefinition;
  can_afford: boolean;
}
