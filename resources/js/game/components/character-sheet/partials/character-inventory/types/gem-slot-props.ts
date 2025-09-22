import BaseGemDetails from '../../../../../api-definitions/items/base-gem-details';

export default interface GemSlotProps {
  gem_slot: BaseGemDetails;
  on_view_gem: (slotId: number) => void;
}
