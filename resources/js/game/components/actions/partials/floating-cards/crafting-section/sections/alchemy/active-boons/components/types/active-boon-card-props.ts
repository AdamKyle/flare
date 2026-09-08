import ActiveBoonDefinition from '../../api/definitions/active-boon-definition';

export default interface ActiveBoonCardProps {
  boon: ActiveBoonDefinition;
  filling: boolean;
  removing: boolean;
  on_view_source_item: () => void;
  on_fill_up: () => void;
  on_remove: () => void;
}
