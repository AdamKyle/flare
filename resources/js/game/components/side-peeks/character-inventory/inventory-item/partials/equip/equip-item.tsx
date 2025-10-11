import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isNil } from 'lodash';
import React from 'react';

import EquipComparison from './equip-comparison';
import { TOP_ADVANCED_CHILD_FIELDS } from '../../../../../../reusable-components/item/constants/item-comparison-constants';
import { ItemBaseTypes } from '../../../../../../reusable-components/item/enums/item-base-type';
import EquipItemActions from '../../../../../../reusable-components/item/equip-item-actions';
import { getType } from '../../../../../../reusable-components/item/utils/get-type';
import { hasAnyNonZeroAdjustment } from '../../../../../../reusable-components/item/utils/item-comparison';
import {
  armourPositions,
  InventoryItemTypes,
} from '../../../../../character-sheet/partials/character-inventory/enums/inventory-item-types';
import { planeTextItemColors } from '../../../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import UseEquipItemRequestParamsDefinition from '../../api/definitions/use-equip-item-request-params-definition';
import { useEquipItem } from '../../api/hooks/use-equip-item';
import { useGetInventoryItemComparisonDetails } from '../../api/hooks/use-get-inventory-item-comparison-details';
import EquipItemProps from '../../types/partials/equip/equip-item-props';
import ItemMetaSection from '../item-view/item-meta-tsx';

import { GameDataError } from 'game-data/components/game-data-error';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';
import PillTabs from 'ui/tabs/pill-tabs';

const EquipItem = ({
  character_id,
  item_to_equip_type,
  slot_id,
  on_equip,
}: EquipItemProps) => {
  const { loading, error, data } = useGetInventoryItemComparisonDetails({
    character_id: character_id,
    slot_id: slot_id,
    item_to_equip_type: item_to_equip_type,
  });

  const {
    loading: equipmentIsLoading,
    error: equipmentError,
    setRequestParams: setEquipmentRequestParams,
  } = useEquipItem({
    character_id: character_id,
    on_success: on_equip,
  });

  if (loading) {
    return (
      <div className=" p-4">
        <InfiniteLoader />
      </div>
    );
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  if (isNil(data)) {
    return <GameDataError />;
  }

  const itemToEquip = data[0].item_to_equip;

  const showAdvancedChildUnderTop = data.some((row) =>
    hasAnyNonZeroAdjustment(
      row.comparison.adjustments,
      TOP_ADVANCED_CHILD_FIELDS
    )
  );

  const handleEquipItem = (
    requestParams: UseEquipItemRequestParamsDefinition
  ) => {
    setEquipmentRequestParams({
      slot_id: requestParams.slot_id,
      equip_type: requestParams.equip_type,
      position: requestParams.position,
    });
  };

  const resolveTabLabels = () => {
    const hasData = Array.isArray(data) && data.length > 0;

    if (!hasData) {
      return null;
    }

    const baseType = getType(itemToEquip, armourPositions);

    const isShield = itemToEquip.type === InventoryItemTypes.SHIELD;

    if (baseType === ItemBaseTypes.Weapon || isShield) {
      return ['Left Hand', 'Right Hand'];
    }

    if (baseType === ItemBaseTypes.Ring) {
      return ['Ring One', 'Ring Two'];
    }

    if (baseType === ItemBaseTypes.Spell) {
      return ['Spell One', 'Spell Two'];
    }

    return null;
  };

  const renderTabsOrComparison = () => {
    const labels = resolveTabLabels();

    if (!labels) {
      return (
        <>
          <Separator />
          <EquipComparison
            comparison_data={data[0]}
            show_advanced_child_under_top={showAdvancedChildUnderTop}
          />
        </>
      );
    }

    const tabs = labels.map((label, index) => {
      return {
        label: label,
        component: EquipComparison,
        props: {
          comparison_data: data[index],
          show_advanced_child_under_top: showAdvancedChildUnderTop,
        },
      };
    });

    return (
      <div className="flex justify-center">
        <PillTabs tabs={tabs} />
      </div>
    );
  };

  const renderEquipError = () => {
    if (!equipmentError) {
      return null;
    }

    return <ApiErrorAlert apiError={equipmentError.message} />;
  };

  return (
    <>
      <div className="px-4">
        <ItemMetaSection
          name={itemToEquip.name}
          description={itemToEquip.description}
          type={itemToEquip.type}
          titleClassName={planeTextItemColors(itemToEquip)}
        />
        <Separator />
      </div>
      {renderEquipError()}
      <EquipItemActions
        comparison_details={data}
        on_confirm_action={handleEquipItem}
        is_processing={equipmentIsLoading}
        is_equipping
      />
      <Separator />
      {renderTabsOrComparison()}
    </>
  );
};

export default EquipItem;
