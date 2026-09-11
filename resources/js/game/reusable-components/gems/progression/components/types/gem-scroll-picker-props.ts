export default interface GemScrollPickerProps {
  character_id: number;
  gem_scroll_type: 'xp' | 'currency' | 'item';
  gem_scroll_currency_type?: string | null;
  disabled?: boolean;
  on_select: (alchemyBagSlotId: number) => void;
}
