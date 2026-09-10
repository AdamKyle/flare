import ActiveBoonDefinition from '../../../crafting-section/sections/alchemy/active-boons/api/definitions/active-boon-definition';

export default interface CharacterAlchemyBoonsProps {
  boons: ActiveBoonDefinition[];
  on_open: () => void;
}
