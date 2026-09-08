import ActiveBoonDefinition from '../../../crafting-section/sections/alchemy/active-boons/api/definitions/active-boon-definition';

export default interface CharacterAlchemyBoonsProps {
  boons: ActiveBoonDefinition[];
  loading: boolean;
  on_open: () => void;
  on_complete: () => void;
}
