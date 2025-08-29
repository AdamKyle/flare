import React, { ReactNode } from 'react';

import { useManageShopSectionVisibility } from '../../../../shop/hooks/use-manage-shop-section-visibility';
import FloatingCard from '../../../components/icon-section/floating-card';
import { useManageMarketVisibility } from '../map-section/hooks/use-manage-market-visibility';
import { useManageSetSailButtonState } from '../map-section/hooks/use-manage-set-sail-button-state';
import { useManageShopVisibility } from '../map-section/hooks/use-manage-shop-visibility';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/separator/separator';

const ShopCard = (): ReactNode => {
  const { closeShop } = useManageShopVisibility();
  const { isSetSailEnabled } = useManageSetSailButtonState();
  const { openMarket } = useManageMarketVisibility();
  const { openShopSection } = useManageShopSectionVisibility();

  return (
    <FloatingCard title="Shops" close_action={closeShop}>
      <Button
        label="Purchase Equipment"
        on_click={openShopSection}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Button
        label="Market (Auction House)"
        on_click={openMarket}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
        disabled={!isSetSailEnabled}
      />
      <Button
        label="Goblin Shop"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Separator />
      <Button
        label="Slots"
        on_click={() => {}}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
    </FloatingCard>
  );
};

export default ShopCard;
