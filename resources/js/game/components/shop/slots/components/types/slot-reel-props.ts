import SlotSymbolPresentation from '../../types/slot-symbol-presentation';

export default interface SlotReelProps {
  reel_index: number;
  symbols: SlotSymbolPresentation[];
  is_spinning: boolean;
  target_index: number | null;
  on_stopped: () => void;
}
