import MonsterDefinition from '../../../../../../api-definitions/monsters/monster-definition';

export default interface SelectedCelestialDetailsProps {
  monster: MonsterDefinition;
  on_view_details: () => void;
}
