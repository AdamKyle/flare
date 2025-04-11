
import UsePaginatedApiHandler from "api-handler/hooks/use-paginated-api-handler";
import React from "react";

import InventoryList from "./inventory-list";
import {useInfiniteScroll} from "../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll";
import {CharacterInventoryApiUrls} from "../api/enums/character-inventory-api-urls";
import BackPackItemsProps from "./types/back-pack-items-props";

import {GameDataError} from "game-data/components/game-data-error";

import Button from "ui/buttons/button";
import {ButtonVariant} from "ui/buttons/enums/button-variant-enum";
import InfiniteLoader from "ui/loading-bar/infinite-loader";
import BaseInventoryItemDefinition from "../api-definitions/base-inventory-item-definition";




const BackPackItems = ({character_id, on_switch_view}: BackPackItemsProps) => {
  const { data, error, loading, canLoadMore, isLoadingMore, setPage } = UsePaginatedApiHandler<BaseInventoryItemDefinition>({
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY,
    urlParams: { character: character_id }
  });

  const onEndReached = () => {
    if (!canLoadMore || isLoadingMore) {
      return;
    }

    setPage(prevValue => prevValue + 1)
  }

  const {
    handleScroll: handleInventoryScroll,
  } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });


  if (error) {
    return <GameDataError />;
  }

  if (loading) {
    return <InfiniteLoader />;
  }

  return (
    <div className="flex flex-col h-full overflow-hidden">
      <div className="flex justify-center p-4">
        <Button
          on_click={() => on_switch_view(false)}
          label="Quest Items"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      <div className="flex-1 min-h-0">
        <InventoryList
          items={data}
          is_quest_items={false}
          on_scroll_to_end={handleInventoryScroll}
        />
      </div>
    </div>
  );
}

export default BackPackItems;