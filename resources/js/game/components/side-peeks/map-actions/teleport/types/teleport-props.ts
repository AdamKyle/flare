import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface TeleportProps extends SidePeekProps {
  character_id: number;
  x: number;
  y: number;
}
