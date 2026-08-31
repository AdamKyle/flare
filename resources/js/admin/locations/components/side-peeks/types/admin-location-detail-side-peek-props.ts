import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface AdminLocationDetailSidePeekProps extends SidePeekProps {
  location_id: number;
  on_location_changed?: () => void;
}
