import React, { ReactNode } from 'react';

import QuestBrowseControlsProps from '../types/quest-browse-controls-props';

import Dropdown from 'ui/drop-down/drop-down';

const QuestBrowseControls = ({
  id,
  game_maps: gameMaps,
  selected_game_map_id: selectedGameMapId,
  on_select_game_map: onSelectGameMap,
}: QuestBrowseControlsProps): ReactNode => (
  <div className="mb-4 w-full sm:max-w-xs">
    <Dropdown
      id={id}
      aria_label="Plane"
      searchable
      items={gameMaps}
      pre_selected_item={gameMaps.find(
        (item) => item.value === selectedGameMapId
      )}
      on_select={(item) => onSelectGameMap(Number(item.value))}
      selection_placeholder="Select Plane"
    />
  </div>
);

export default QuestBrowseControls;
