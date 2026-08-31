import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface AdminQuestDetailSidePeekProps extends SidePeekProps {
  quest_id: number;
  on_quest_changed?: () => void;
}
