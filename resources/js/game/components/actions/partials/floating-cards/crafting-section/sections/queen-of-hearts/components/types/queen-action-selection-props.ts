import { QueenAction } from '../../enums/queen-action';

export default interface QueenActionSelectionProps {
  onSelect: (action: QueenAction) => void;
}
