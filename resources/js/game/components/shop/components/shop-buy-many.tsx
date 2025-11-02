import { debounce } from 'lodash';
import React, { useEffect, useMemo, useState } from 'react';

import ShopCardDetails from './shop-card-details';
import Section from '../../../reusable-components/viewable-sections/section';
import { UsePurchaseManyItems } from '../api/hooks/use-purchase-many-items';
import ShopBuyManyProps from '../types/shop-buy-many-props';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';
import { useGameData } from 'game-data/hooks/use-game-data';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const ShopBuyMany = ({ item, on_close }: ShopBuyManyProps) => {
  const { gameData, updateCharacter } = useGameData();

  const [quantityInput, setQuantityInput] = useState<string>('');
  const [quantity, setQuantity] = useState(0);
  const [canBuyItems, setCanBuyItems] = useState(false);
  const [cannotBuyReason, setCannotBuyReason] = useState<string>('');

  const handlePurchaseSuccess = (
    character: Partial<CharacterSheetDefinition>
  ) => {
    setQuantity(0);
    setCanBuyItems(false);
    setCannotBuyReason('');
    setQuantityInput('');

    updateCharacter(character);
  };

  const { successMessage, loading, error, setRequestParams } =
    UsePurchaseManyItems({
      character_id: gameData?.character?.id || 0,
      on_success: handlePurchaseSuccess,
    });

  const parseQuantity = (value: string) => {
    const amountToBuy = parseInt(value, 10);

    if (Number.isNaN(amountToBuy) || amountToBuy < 0) {
      setQuantity(0);

      return;
    }

    setQuantity(amountToBuy);
  };

  const debouncedParseQuantity = useMemo(
    () => debounce(parseQuantity, 200),
    []
  );

  useEffect(() => debouncedParseQuantity.cancel, [debouncedParseQuantity]);

  const handleChangeQuantity = (next: string) => {
    setQuantityInput(next);
    debouncedParseQuantity(next);
  };

  const handlePurchaseMany = () => {
    setRequestParams({
      item_id: item.item_id,
      amount: quantity,
    });
  };

  const subtotal = quantity * Number(item.cost ?? 0);
  const totalWithTax = Math.round(subtotal * 1.05);

  const characterInfo = gameData?.character;

  useEffect(() => {
    const hasInput = quantityInput.trim().length > 0;

    if (!characterInfo) {
      setCanBuyItems(false);
      setCannotBuyReason('');

      return;
    }

    if (!hasInput) {
      setCanBuyItems(false);
      setCannotBuyReason('');

      return;
    }

    if (quantity <= 0) {
      setCanBuyItems(false);
      setCannotBuyReason('');

      return;
    }

    if (totalWithTax > characterInfo.gold) {
      setCanBuyItems(false);
      setCannotBuyReason('You cannot afford this.');

      return;
    }

    const totalQuantityForInventory =
      quantity + characterInfo.inventory_count.data.inventory_count;

    if (
      totalQuantityForInventory >
      characterInfo.inventory_count.data.inventory_max
    ) {
      setCanBuyItems(false);
      setCannotBuyReason('You do not have enough inventory space.');

      return;
    }

    setCanBuyItems(true);
    setCannotBuyReason('');
  }, [quantityInput, quantity, totalWithTax, characterInfo]);

  const renderCharacterSummary = () => {
    if (!gameData?.character) {
      return null;
    }

    const currentCount =
      gameData.character.inventory_count.data.inventory_count;

    const maxCount = gameData.character.inventory_count.data.inventory_max;

    const gold = gameData.character.gold;

    const bagCount =
      gameData.character.inventory_count.data.inventory_bag_count;

    const usableItemsCount =
      gameData.character.inventory_count.data.alchemy_item_count;

    const gemBagCount = gameData.character.inventory_count.data.gem_bag_count;

    return (
      <Section title="Character" showSeparator={false} showTitleSeparator>
        <Dt>
          <span className="inline-flex items-center gap-2">
            <GeneralToolTip
              label="Inventory Break Down"
              align="right"
              size="sm"
              message={
                <Section
                  title="Inventory Break Down"
                  showSeparator={false}
                  showTitleSeparator={false}
                >
                  <Dt>
                    <span className="text-marigold-600 dark:text-mango-tango-400">
                      Bag Count
                    </span>
                  </Dt>
                  <Dd>
                    <span className="font-semibold">
                      {formatNumberWithCommas(bagCount)}
                    </span>
                  </Dd>

                  <Dt>
                    <span className="text-marigold-600 dark:text-mango-tango-400">
                      Usable Items Count
                    </span>
                  </Dt>
                  <Dd>
                    <span className="font-semibold">
                      {formatNumberWithCommas(usableItemsCount)}
                    </span>
                  </Dd>

                  <Dt>
                    <span className="text-marigold-600 dark:text-mango-tango-400">
                      Gem Bag Count
                    </span>
                  </Dt>
                  <Dd>
                    <span className="font-semibold">
                      {formatNumberWithCommas(gemBagCount)}
                    </span>
                  </Dd>

                  <Dt>
                    <span className="text-marigold-600 dark:text-mango-tango-400">
                      Total Inventory Count
                    </span>
                  </Dt>
                  <Dd>
                    <span className="font-semibold">
                      {formatNumberWithCommas(currentCount)}
                    </span>
                  </Dd>
                </Section>
              }
            />
            <span className="text-marigold-600 dark:text-mango-tango-400">
              Inventory Count
            </span>
          </span>
        </Dt>
        <Dd>
          <span className="font-semibold">
            {formatNumberWithCommas(currentCount)}
          </span>
        </Dd>

        <Dt>
          <span className="text-marigold-600 dark:text-mango-tango-400">
            Inventory Max
          </span>
        </Dt>
        <Dd>
          <span className="font-semibold">
            {formatNumberWithCommas(maxCount)}
          </span>
        </Dd>

        <Dt>
          <span className="text-marigold-600 dark:text-mango-tango-400">
            Gold
          </span>
        </Dt>
        <Dd>
          <span className="font-semibold">{formatNumberWithCommas(gold)}</span>
        </Dd>
      </Section>
    );
  };

  const renderCannotBuyMessage = () => {
    if (canBuyItems && !cannotBuyReason) {
      return null;
    }

    return (
      <p className="mt-2 text-sm font-medium text-rose-500 dark:text-rose-400">
        {cannotBuyReason}
      </p>
    );
  };

  const renderCostSection = () => {
    if (quantity === 0) {
      return null;
    }

    return (
      <>
        <Separator />
        <Section
          title="Purchase Summary"
          showSeparator={false}
          showTitleSeparator={false}
        >
          <Dt>
            <span className="inline-flex items-center gap-2">
              <GeneralToolTip
                label="Cost includes a 5% sales tax applied"
                align="right"
                size="sm"
              />
              <span>Cost</span>
            </span>
          </Dt>
          <Dd>
            <span className="font-semibold">
              {formatNumberWithCommas(totalWithTax)}
            </span>
          </Dd>
        </Section>
        {renderCannotBuyMessage()}
      </>
    );
  };

  const renderPurchasingMultipleItems = () => {
    if (!loading) {
      return null;
    }

    return (
      <>
        <InfiniteLoader />
        <Separator />
      </>
    );
  };

  const renderErrorMessage = () => {
    if (!error) {
      return null;
    }

    return (
      <>
        <Alert variant={AlertVariant.DANGER}>{error.message}</Alert>
        <Separator />
      </>
    );
  };

  const renderSuccessMessage = () => {
    if (!successMessage) {
      return null;
    }

    return (
      <>
        <Alert variant={AlertVariant.SUCCESS}>{successMessage}</Alert>
        <Separator />
      </>
    );
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title={`Buy many of: ${item.name}`}
    >
      <Card>
        <ShopCardDetails item={item} />
        <Separator />
        <div className="mx-auto w-full px-4 pt-2 md:w-1/2">
          {renderCharacterSummary()}
          <Separator />
          <div>
            {renderPurchasingMultipleItems()}
            {renderSuccessMessage()}
            {renderErrorMessage()}
          </div>
          <div className="flex flex-nowrap items-center gap-3">
            <div className="min-w-0 flex-1">
              <Input
                value={quantityInput}
                on_change={handleChangeQuantity}
                place_holder="Enter Amount to buy"
                clearable
                disabled={loading}
              />
            </div>
            <div className="shrink-0">
              <Button
                on_click={handlePurchaseMany}
                label="Purchase"
                variant={ButtonVariant.SUCCESS}
                disabled={!canBuyItems || loading}
              />
            </div>
          </div>
          <div className="mt-2">{renderCostSection()}</div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};

export default ShopBuyMany;
