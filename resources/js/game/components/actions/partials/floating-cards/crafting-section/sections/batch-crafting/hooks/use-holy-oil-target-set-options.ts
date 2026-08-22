import UseHolyOilTargetSetOptionsDefinition from './definitions/use-holy-oil-target-set-options-definition';
import UseHolyOilTargetSetOptionsProps from './types/use-holy-oil-target-set-options-props';
import { useHolyOilTargetSetOptionsApi } from '../../../../../../../side-peeks/character-inventory/sets/api/hooks/use-inventory-set-options-api';

export const useHolyOilTargetSetOptions = ({
  characterId,
  enabled,
}: UseHolyOilTargetSetOptionsProps): UseHolyOilTargetSetOptionsDefinition => {
  const {
    setOptions,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  } = useHolyOilTargetSetOptionsApi({ characterId, enabled });

  return {
    setOptions,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  };
};
