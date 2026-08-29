import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface AdminItemDetailSidePeekProps extends SidePeekProps {
  item_id: number;
  on_item_changed?: () => void;
}
