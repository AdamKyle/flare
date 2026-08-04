import { SeerAction } from '../../enums/seer-action';
export default interface SeerActionSelectionProps {
  onSelect: (action: SeerAction) => void;
}
