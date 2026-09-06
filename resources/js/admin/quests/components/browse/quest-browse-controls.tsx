import React, { ReactNode } from 'react';

import QuestBrowseControlsProps from './types/quest-browse-controls-props';
import AdminAnchorButton from '../../../shared/components/admin-anchor-button';
import { parseNumberOption } from '../../utils/parse-quest-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';

/**
 * Admin Quest browse top controls: the plane-first Game Map selector and
 * the Import/Export actions. The Quest category is chosen separately via
 * the primary Base / One Offs / Raid tabs rendered below this row.
 */
const QuestBrowseControls = ({
  game_maps: gameMaps,
  selected_game_map_id: selectedGameMapId,
  on_select_game_map: onSelectGameMap,
  importing,
  on_import_click: onImportClick,
}: QuestBrowseControlsProps): ReactNode => (
  <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
    <div className="w-full sm:max-w-xs">
      <Dropdown
        id="quest-plane-filter"
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

    <div className="flex flex-wrap items-center gap-2">
      <Button
        label={importing ? 'Importing…' : 'Import'}
        variant={ButtonVariant.PRIMARY}
        on_click={onImportClick}
        disabled={importing}
      />
      <AdminAnchorButton
        href="/admin/quests/export"
        label="Export"
        variant={ButtonVariant.PRIMARY}
      />
    </div>
  </div>
);

export default QuestBrowseControls;
