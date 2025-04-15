import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import React, { useMemo } from 'react';

import SetOptionDefinition from './definitions/set-options-definition';
import SetChoicesProps from './types/set-choices-props';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const SetChoices = ({
  character_id,
  on_set_change,
  on_set_selection_clear,
}: SetChoicesProps) => {
  const { data, error, loading, onEndReached } =
    UsePaginatedApiHandler<SetOptionDefinition>({
      url: CharacterInventoryApiUrls.CHARACTER_SET_CHOICES,
      urlParams: { character: character_id },
    });

  const { handleScroll: handleSetSelectionScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  const setOptions = useMemo(() => {
    return data.map((set: SetOptionDefinition) => ({
      label: set.name,
      value: set.set_id,
    }));
  }, [data]);

  const handleSetSelection = (selectedSet: DropdownItem) => {
    on_set_change(selectedSet);
  };

  const handleClearSection = () => {
    on_set_selection_clear();
  };

  if (error) {
    return <GameDataError />;
  }

  if (loading) {
    return <InfiniteLoader />;
  }

  return (
    <Dropdown
      items={setOptions}
      on_select={handleSetSelection}
      on_clear={handleClearSection}
      handle_scroll={handleSetSelectionScroll}
      selection_placeholder={'Select a set'}
      pre_selected_item={setOptions[0]}
      use_pagination
    />
  );
};

export default SetChoices;
