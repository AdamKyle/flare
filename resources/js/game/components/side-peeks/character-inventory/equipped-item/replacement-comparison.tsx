import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import ReplacementComparisonProps from './types/replacement-comparison-props';
import { resolveItemPosition } from './utils/resolve-item-position';
import { TOP_ADVANCED_CHILD_FIELDS } from '../../../../reusable-components/item/constants/item-comparison-constants';
import { hasAnyNonZeroAdjustment } from '../../../../reusable-components/item/utils/item-comparison';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import InventoryStackBody from '../components/inventory-stack-body';
import { useEquipItem } from '../inventory-item/api/hooks/use-equip-item';
import { useGetInventoryItemComparisonDetails } from '../inventory-item/api/hooks/use-get-inventory-item-comparison-details';
import EquipComparison from '../inventory-item/partials/equip/equip-comparison';
import ItemMetaSection from '../inventory-item/partials/item-view/item-meta-tsx';

import { GameDataError } from 'game-data/components/game-data-error';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const ReplacementComparison = ({
  character_id,
  candidate,
  target_position,
  is_equipment_restricted,
  on_close,
  on_equipped,
}: ReplacementComparisonProps): ReactNode => {
  const { loading, error, data } = useGetInventoryItemComparisonDetails({
    character_id,
    slot_id: candidate.slot_id,
    item_to_equip_type: candidate.type,
  });

  const {
    loading: isEquipping,
    error: equipError,
    setRequestParams: setEquipRequestParams,
  } = useEquipItem({
    character_id,
    on_success: on_equipped,
  });

  const targetItemPosition = resolveItemPosition(target_position);

  const handleEquip = (): void => {
    setEquipRequestParams({
      slot_id: candidate.slot_id,
      position: targetItemPosition,
      equip_type: candidate.type,
    });
  };

  const resolveFooterOptions = (): SidePeekOptionDefinition[] => {
    if (targetItemPosition === null) {
      return [];
    }

    return [
      {
        id: 'equip-replacement',
        label: 'Equip',
        loading_label: 'Equipping...',
        aria_label: `Equip ${candidate.name} in place of the equipped item`,
        variant: ButtonVariant.SUCCESS,
        loading: isEquipping,
        disabled: is_equipment_restricted,
        on_click: handleEquip,
      },
    ];
  };

  const comparisonRow = data?.details.find(
    (row) => row.position === String(target_position)
  );

  const showAdvancedChildUnderTop = (data?.details ?? []).some((row) =>
    hasAnyNonZeroAdjustment(
      row.comparison.adjustments,
      TOP_ADVANCED_CHILD_FIELDS
    )
  );

  const renderEquipError = (): ReactNode => {
    if (!equipError) {
      return null;
    }

    return <ApiErrorAlert apiError={equipError.message} />;
  };

  const renderBody = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (!data) {
      return <GameDataError />;
    }

    return (
      <EquipComparison
        comparison_data={comparisonRow}
        show_advanced_child_under_top={showAdvancedChildUnderTop}
      />
    );
  };

  return (
    <StackedCard
      on_close={on_close}
      aria_label="Compare Replacement"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <InventoryStackBody footer_options={resolveFooterOptions()}>
        <div className="flex flex-col gap-4 px-4">
          <ItemMetaSection
            name={candidate.name}
            description={candidate.description}
            type={candidate.type}
            titleClassName={planeTextItemColors(candidate)}
          />
          <Separator />
          {renderEquipError()}
          {renderBody()}
        </div>
      </InventoryStackBody>
    </StackedCard>
  );
};

export default ReplacementComparison;
