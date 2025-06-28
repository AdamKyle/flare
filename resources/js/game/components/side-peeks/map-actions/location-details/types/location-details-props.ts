import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface LocationDetailsProps extends SidePeekProps {
  character_id: number;
  location_id: number;
  character_x: number;
  character_y: number;
  character_gold: number;
  go_back?: () => void;
  show_title?: boolean;
}
