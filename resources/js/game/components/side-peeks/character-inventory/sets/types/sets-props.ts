import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface SetsProps extends SidePeekProps {
  character_id: number;
  initial_search_text?: string;
  initial_set_id?: number;
  initial_set_name?: string;
}
