import { debounce } from 'lodash';
import React, { useEffect, useMemo, useState } from 'react';

import ShopCardDetails from './shop-card-details';
import StatInfoToolTip from '../../../reusable-components/item/stat-info-tool-tip';
import Section from '../../../reusable-components/viewable-sections/section';
import { formatNumberWithCommas } from '../../../util/format-number';
import ShopBuyManyProps from '../types/shop-buy-many-props';

import { useGameData } from 'game-data/hooks/use-game-data';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Dd from 'ui/dl/dd';
import Dt from 'ui/dl/dt';
import Input from 'ui/input/input';
import Separator from 'ui/separator/separator';

const ShopBuyMany = ({ item, on_close }: ShopBuyManyProps) => {
  const { gameData } = useGameData();

  const [quantityInput, setQuantityInput] = useState<string>('');
  const [quantity, setQuantity] = useState(0);
  const [canBuyItems, setCanBuyItems] = useState(false);

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

  const subtotal = quantity * Number(item.cost ?? 0);
  const totalWithTax = Math.round(subtotal * 1.05);

  const characterInfo = gameData?.character;

  useEffect(() => {
    const hasInput = quantityInput.trim().length > 0;
    if (!characterInfo) {
      setCanBuyItems(false);
      return;
    }
    if (!hasInput) {
      setCanBuyItems(false);
      return;
    }
    if (quantity <= 0) {
      setCanBuyItems(false);
      return;
    }
    if (totalWithTax > characterInfo.gold) {
      setCanBuyItems(false);
      return;
    }
    setCanBuyItems(true);
  }, [quantityInput, quantity, totalWithTax, characterInfo]);

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
              <StatInfoToolTip
                label="Cost includes a 5% sales tax applied"
                value={totalWithTax}
                align="right"
                size="sm"
                custom_message
              />
              <span>Cost</span>
            </span>
          </Dt>
          <Dd>
            <span className="font-semibold">
              {formatNumberWithCommas(totalWithTax)} gold
            </span>
          </Dd>
        </Section>
        {!canBuyItems ? (
          <p className="mt-2 text-sm font-medium text-rose-500 dark:text-rose-400">
            You cannot afford this.
          </p>
        ) : null}
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
        <div className="pt-2 px-4 w-full md:w-1/2 mx-auto">
          <div className="flex flex-nowrap items-center gap-3">
            <div className="min-w-0 flex-1">
              <Input
                value={quantityInput}
                on_change={handleChangeQuantity}
                place_holder="Enter Amount to buy"
                clearable
              />
            </div>
            <div className="shrink-0">
              <Button
                on_click={() => {}}
                label="Purchase"
                variant={ButtonVariant.SUCCESS}
                disabled={!canBuyItems}
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
