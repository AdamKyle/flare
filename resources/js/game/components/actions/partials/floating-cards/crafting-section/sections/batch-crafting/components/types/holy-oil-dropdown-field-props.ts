import WorkBenchAlchemySlotDefinition from '../../../work-bench/api/definitions/work-bench-alchemy-slot-definition';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export default interface HolyOilDropdownFieldProps {
  legend_id: string;
  loaded_items: WorkBenchAlchemySlotDefinition[];
  selected_oil: DropdownItem | null;
  loading: boolean;
  can_load_more: boolean;
  is_loading_more: boolean;
  search_text: string;
  on_search: (value: string) => void;
  on_end_reached: () => void;
  on_select: (item: DropdownItem | null) => void;
}
