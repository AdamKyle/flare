import SlotSymbolDefinition from '../../types/slot-symbol-definition';

export default interface SlotsResponseDefinition {
  icons: SlotSymbolDefinition[];
  can_spin: boolean;
  timeout_for: number;
  spin_cost: number;
}
