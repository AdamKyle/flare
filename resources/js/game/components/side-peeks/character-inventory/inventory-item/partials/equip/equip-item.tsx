import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React from 'react';

import LeftHandComparison from './left-hand-comparison';
import RightHandComparison from './right-hand-comparison';
import { useGetInventoryItemComparisonDetails } from '../../api/hooks/use-get-inventory-item-comparison-details';
import EquipItemProps from '../../types/partials/equip/equip-item-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';
import PillTabs from 'ui/tabs/pill-tabs';

const EquipItem = ({
  on_close,
  character_id,
  item_to_equip_type,
  slot_id,
}: EquipItemProps) => {
  const { loading, error, data } = useGetInventoryItemComparisonDetails({
    character_id: character_id,
    slot_id: slot_id,
    item_to_equip_type: item_to_equip_type,
  });

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  const tabs = [
    { label: 'Hello', component: LeftHandComparison },
    { label: 'Something', component: RightHandComparison },
  ] as const;

  console.log('EquipItem data', data);

  return (
    <>
      <div className="text-center p-4">
        <Button
          on_click={on_close}
          label="Close"
          variant={ButtonVariant.DANGER}
        />
      </div>
      <Separator />
      <div className="flex justify-center">
        <PillTabs tabs={tabs} />
      </div>
    </>
  );
};

export default EquipItem;
