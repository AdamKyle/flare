import React, { ReactNode } from 'react';

import QuestInfoBrowseControlsProps from './types/quest-info-browse-controls-props';
import { parseNumberOption } from '../utils/parse-quest-info-dropdown-value';

import Dropdown from 'ui/drop-down/drop-down';

/**
 * Public Quest browse top control: the plane-first Game Map selector. The
 * Quest category is chosen separately via the primary Base / One Offs /
 * Raid tabs rendered below this control.
 */
const QuestInfoBrowseControls = ({
  game_maps: gameMaps,
  selected_game_map_id: selectedGameMapId,
  on_select_game_map: onSelectGameMap,
}: QuestInfoBrowseControlsProps): ReactNode => (
  <div className="mb-4 w-full sm:max-w-xs">
    <Dropdown
      id="quest-info-plane-filter"
      aria_label="Plane"
      searchable
      items={gameMaps}
      pre_selected_item={gameMaps.find(
        (item) => item.value === selectedGameMapId
      )}
      on_select={(item) => onSelectGameMap(parseNumberOption(item.value))}
      selection_placeholder="Select Plane"
    />
  </div>
);

export default QuestInfoBrowseControls;
