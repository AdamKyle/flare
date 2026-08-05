import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import React, { useMemo } from 'react';

import SetOptionDefinition from './definitions/set-options-definition';
import SetChoicesProps from './types/set-choices-props';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const SetChoices = ({
  character_id,
  on_set_change,
  on_set_selection_clear,
  set_equipped_set_name,
  dont_show_equipped_set,
}: SetChoicesProps) => {
  const { data, error, loading, canLoadMore, isLoadingMore, onEndReached } =
    UsePaginatedApiHandler<SetOptionDefinition>({
      url: CharacterInventoryApiUrls.CHARACTER_SET_CHOICES,
      urlParams: { character: character_id },
    });

  const setOptions = useMemo((): DropdownItem[] => {
    if (dont_show_equipped_set) {
      return data
        .filter((set) => !set.equipped)
        .map((set: SetOptionDefinition) => ({
          label: set.name,
          value: set.set_id,
        }));
    }

    return data.map((set: SetOptionDefinition) => ({
      label: set.name,
      value: set.set_id,
    }));
  }, [data]);

  const preSelectedSetOption = useMemo((): DropdownItem | undefined => {
    const equippedSet = data.find((set: SetOptionDefinition) => set.equipped);

    if (!equippedSet) {
      return undefined;
    }

    return { label: equippedSet.name, value: equippedSet.set_id };
  }, [data]);

  const setPreSelectedOption = () => {
    if (!set_equipped_set_name) {
      return undefined;
    }

    return preSelectedSetOption ?? setOptions[0];
  };

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
      on_end_reached={onEndReached}
      can_load_more={canLoadMore}
      is_loading_more={isLoadingMore}
      selection_placeholder="Select a set"
      pre_selected_item={setPreSelectedOption()}
    />
  );
};

export default SetChoices;
