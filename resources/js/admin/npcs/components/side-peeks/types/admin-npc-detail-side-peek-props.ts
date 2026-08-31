import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface AdminNpcDetailSidePeekProps extends SidePeekProps {
  npc_id: number;
  on_npc_changed?: () => void;
}
