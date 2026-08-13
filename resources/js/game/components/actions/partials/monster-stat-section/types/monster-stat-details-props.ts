import MonsterDefinition from '../../../../../api-definitions/monsters/monster-definition';

export default interface MonsterStatDetailsProps {
  monster: MonsterDefinition;
  single_column?: boolean;
}
