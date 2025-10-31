import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { AnimatePresence } from 'framer-motion';
import React, { useMemo, useState } from 'react';

import UseFetchTraversableMapsResponse from './api/hooks/deffinitions/use-fetch-traversable-maps-response';
import { useOpenTraverseModal } from './api/hooks/use-fetch-traversable-maps';
import TraversePropsDefinition from './definitions/traverse-props-definition';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import QuestItem from '../../character-inventory/inventory-item/quest-item';
import GenericItem from '../../components/items/generic-item';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

export const Traverse = ({ character_data }: TraversePropsDefinition) => {
  const { data, loading, error } = useOpenTraverseModal();

  const [selectedMap, setSelectedMap] = useState<DropdownItem | null>(null);
  const [mapDetails, setMapDetails] =
    useState<UseFetchTraversableMapsResponse | null>(null);
  const [questItemToView, setQuestItemToView] =
    useState<BaseQuestItemDefinition | null>(null);

  const mapsToSelect = useMemo(() => {
    if (!data) {
      return [];
    }

    return data.map((map) => {
      return {
        label: map.name,
        value: map.id,
      };
    });
  }, [data]);

  const currentSelectedMap = useMemo<DropdownItem | undefined>(() => {
    if (!data) {
      return undefined;
    }

    if (!selectedMap) {
      const foundCharacterMap = data.find(
        (map) => map.id === character_data.game_map_id
      );

      if (!foundCharacterMap) {
        return undefined;
      }

      return {
        label: foundCharacterMap.name,
        value: foundCharacterMap.id,
      };
    }

    return selectedMap;
  }, [character_data.game_map_id, data, selectedMap]);

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  if (!data) {
    return <GameDataError />;
  }

  const handleSelectMap = (selected: DropdownItem) => {
    setSelectedMap(selected);

    const foundMapDetails = data.find((map) => map.id === selected.value);

    if (!foundMapDetails) {
      return;
    }

    setMapDetails(foundMapDetails);
  };

  const handleClearSelection = () => {
    setSelectedMap(null);

    const foundCharacterMap =
      data.find((map) => map.id === character_data.game_map_id) || null;

    setMapDetails(foundCharacterMap);
  };

  const handleViewQuestItem = (
    item: BaseQuestItemDefinition | EquippableItemWithBase
  ) => {
    setQuestItemToView(item as BaseQuestItemDefinition);
  };

  const handleCloseQuestItemToView = () => {
    setQuestItemToView(null);
  };

  const isTraverseDisabled = () => {
    if (!mapDetails) {
      return true;
    }

    return mapDetails.id === character_data.game_map_id;
  };

  const renderQuestItemForMap = () => {
    let map: UseFetchTraversableMapsResponse | null = mapDetails;

    if (!map && !selectedMap) {
      map = data.find((m) => m.id === character_data.game_map_id) || null;
    }

    if (!map || !map.map_required_item) {
      return null;
    }

    return (
      <GenericItem
        key={map.map_required_item.item_id}
        item={map.map_required_item}
        on_click={handleViewQuestItem}
      />
    );
  };

  const renderQuestItemView = () => {
    if (!questItemToView) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseQuestItemToView}>
        <QuestItem quest_item={questItemToView} />
      </StackedCard>
    );
  };

  const renderSelectedMapDetails = () => {
    let map: UseFetchTraversableMapsResponse | null = mapDetails;

    if (!map && !selectedMap) {
      map = data.find((m) => m.id === character_data.game_map_id) || null;
    }

    if (!map) {
      return null;
    }

    return (
      <>
        <h3>{map.name}</h3>
        <p>Some description</p>
        <Separator />
        {renderQuestItemForMap()}
      </>
    );
  };

  return (
    <div className="p-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
      <div className="grid gap-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Traversable Maps
        </label>
        <Dropdown
          items={mapsToSelect}
          on_select={handleSelectMap}
          on_clear={handleClearSelection}
          pre_selected_item={currentSelectedMap}
        />
        <Button
          on_click={() => {}}
          label={'Traverse'}
          variant={ButtonVariant.PRIMARY}
          additional_css={'my-4'}
          disabled={isTraverseDisabled()}
        />
        <Separator />
        {renderSelectedMapDetails()}
      </div>
      <AnimatePresence mode="wait">{renderQuestItemView()}</AnimatePresence>
    </div>
  );
};

export default Traverse;
