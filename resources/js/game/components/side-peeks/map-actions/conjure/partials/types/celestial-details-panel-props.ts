import MonsterDefinition from '../../../../../../api-definitions/monsters/monster-definition';

export default interface CelestialDetailsPanelProps {
  monster: MonsterDefinition;
  on_close: () => void;
}
