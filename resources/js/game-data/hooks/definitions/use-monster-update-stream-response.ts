import MonsterDefinition from 'game-data/api-data-definitions/monsters/monster-definition';

interface NestedMonsterList {
  data: MonsterDefinition[];
}

export default interface UseMonsterUpdateStreamResponse {
  monsters: MonsterDefinition[] | NestedMonsterList;
}
