import React, { ReactNode, useState } from 'react';

import ScreenTransition from '../../../../../reusable-components/screen-transition/screen-transition';
import { useManageGoblinShopVisibility } from '../../../../goblin-shop/hooks/use-manage-goblin-shop-visibility';
import { useEmitCharacterPosition } from '../../../../map-section/hooks/use-emit-character-position';
import { useMarketAccess } from '../../../../market/api/hooks/use-market-access';
import { useManageShopSectionVisibility } from '../../../../shop/hooks/use-manage-shop-section-visibility';
import Slots from '../../../../shop/slots/slots';
import FloatingCard from '../../../components/icon-section/floating-card';
import { useManageMarketVisibility } from '../map-section/hooks/use-manage-market-visibility';
import { useManageShopVisibility } from '../map-section/hooks/use-manage-shop-visibility';

import { useGameData } from 'game-data/hooks/use-game-data';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Separator from 'ui/separator/separator';

const ShopCard = (): ReactNode => {
  const { gameData } = useGameData();
  const { closeShop } = useManageShopVisibility();
  const { openMarket } = useManageMarketVisibility();
  const { openShopSection } = useManageShopSectionVisibility();
  const { openGoblinShop } = useManageGoblinShopVisibility();
  const { characterPosition } = useEmitCharacterPosition();

  const { can_access_market: canAccessMarket } = useMarketAccess({
    character_id: gameData?.character?.id ?? 0,
    refresh_key: `${characterPosition.x}-${characterPosition.y}`,
  });

  const [showSlots, setShowSlots] = useState(false);

  const handleOpenSlots = () => {
    setShowSlots(true);
  };

  const handleCloseSlots = () => {
    setShowSlots(false);
  };

  const renderShopActions = (): ReactNode => (
    <div>
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
        disabled={!canAccessMarket}
      />
      <Button
        label="Goblin Shop"
        on_click={openGoblinShop}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
      <Separator />
      <Button
        label="Slots"
        on_click={handleOpenSlots}
        variant={ButtonVariant.PRIMARY}
        additional_css="w-full my-2"
      />
    </div>
  );

  const renderActiveScreen = (): ReactNode => {
    if (showSlots) {
      return <Slots />;
    }

    return renderShopActions();
  };

  return (
    <FloatingCard
      title={showSlots ? 'Slots' : 'Shops'}
      close_action={closeShop}
      back_action={showSlots ? handleCloseSlots : undefined}
    >
      <ScreenTransition
        screenKey={showSlots ? 'slots' : 'shops'}
        label={showSlots ? 'Slots screen' : 'Shops screen'}
      >
        {renderActiveScreen()}
      </ScreenTransition>
    </FloatingCard>
  );
};

export default ShopCard;
