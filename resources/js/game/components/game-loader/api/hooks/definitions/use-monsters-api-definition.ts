import MonsterDefinition from 'game-data/api-data-definitions/monsters/monster-definition';

export default interface UseMonstersApiDefinition {
  fetchMonstersData: () => Promise<MonsterDefinition[]>;
}
