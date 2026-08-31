import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface AdminMonsterDetailSidePeekProps extends SidePeekProps {
  monster_id: number;
  on_monster_changed?: () => void;
}
