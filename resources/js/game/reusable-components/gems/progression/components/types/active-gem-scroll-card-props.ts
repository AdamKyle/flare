import ActiveGemScrollRowDefinition from '../../api/definitions/active-gem-scroll-row-definition';

export default interface ActiveGemScrollCardProps {
  scroll: ActiveGemScrollRowDefinition;
  character_id: number;
  acting: boolean;
  on_remove: () => void;
  on_fill: (alchemyBagSlotId: number) => void;
}
