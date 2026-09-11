import ActiveGemScrollRowDefinition from '../../api/definitions/active-gem-scroll-row-definition';

export default interface ActiveGemScrollsSectionProps {
  character_id: number;
  scrolls: ActiveGemScrollRowDefinition[];
  on_changed: () => void;
}
