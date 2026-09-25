import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useCallback, useId, useState } from 'react';

import GoblinShopPurchaseFormProps from './types/goblin-shop-purchase-form-props';
import GoblinShopPurchaseResponseDefinition from '../api/definitions/goblin-shop-purchase-response-definition';
import { usePurchaseGoblinShopItem } from '../api/hooks/use-purchase-goblin-shop-item';
import {
  resolveMaxAffordableQuantity,
  validateGoblinShopQuantity,
} from '../utils/validate-goblin-shop-quantity';

import { useGameData } from 'game-data/hooks/use-game-data';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import Input from 'ui/input/input';

const GoblinShopPurchaseForm = ({
  item,
  gold_bars,
  purchase_disabled,
}: GoblinShopPurchaseFormProps): ReactNode => {
  const { gameData, updateCharacter } = useGameData();

  const [quantityInput, setQuantityInput] = useState('1');
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const inputId = useId();
  const errorId = useId();

  const handlePurchaseSuccess = useCallback(
    (result: GoblinShopPurchaseResponseDefinition) => {
      updateCharacter({
        gold_bars: result.character_gold_bars,
        inventory_count: result.inventory_count,
      });
      setSuccessMessage(result.message);
      setQuantityInput('1');
    },
    [updateCharacter]
  );

  const { loading, error, purchase } = usePurchaseGoblinShopItem({
    character_id: gameData?.character?.id ?? 0,
    item_id: item.item_id,
    on_success: handlePurchaseSuccess,
  });

  const unitCost = item.gold_bars_cost ?? 0;
  const maxAffordableQuantity = resolveMaxAffordableQuantity(
    gold_bars,
    unitCost
  );
  const validation = validateGoblinShopQuantity(
    quantityInput,
    maxAffordableQuantity
  );
  const totalCost = (validation.quantity ?? 0) * unitCost;
  const canPurchase =
    !purchase_disabled && validation.quantity !== null && !loading;

  const handleChangeQuantity = (value: string) => {
    setSuccessMessage(null);
    setQuantityInput(value);
  };

  const handlePurchase = () => {
    if (validation.quantity === null) {
      return;
    }

    setSuccessMessage(null);
    void purchase(validation.quantity);
  };

  const renderValidationError = (): ReactNode => {
    if (!validation.error) {
      return null;
    }

    return (
      <p
        id={errorId}
        role="alert"
        className="text-sm font-medium text-rose-600 dark:text-rose-400"
      >
        {validation.error}
      </p>
    );
  };

  const renderApiError = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <ApiErrorAlert apiError={error.message} />;
  };

  const renderSuccessMessage = (): ReactNode => {
    if (!successMessage) {
      return null;
    }

    return <Alert variant={AlertVariant.SUCCESS}>{successMessage}</Alert>;
  };

  return (
    <div className="mt-4 space-y-2">
      <dl className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-700 dark:text-gray-300">
        <dt>Price each</dt>
        <dd className="text-right font-semibold">
          {formatNumberWithCommas(unitCost)} Gold Bars
        </dd>
        <dt>You can afford</dt>
        <dd className="text-right font-semibold">
          {formatNumberWithCommas(maxAffordableQuantity)}
        </dd>
        <dt>Total cost</dt>
        <dd className="text-right font-semibold">
          {formatNumberWithCommas(totalCost)} Gold Bars
        </dd>
      </dl>
      <label
        htmlFor={inputId}
        className="block text-sm font-medium text-gray-800 dark:text-gray-200"
      >
        Amount to buy
      </label>
      <div className="flex flex-nowrap items-center gap-2">
        <div className="min-w-0 flex-1">
          <Input
            id={inputId}
            value={quantityInput}
            on_change={handleChangeQuantity}
            place_holder="Amount to buy"
            disabled={loading || purchase_disabled}
            invalid={validation.error !== null}
            described_by={validation.error ? errorId : undefined}
          />
        </div>
        <LoadingButton
          label="Buy"
          loading_label="Buying..."
          variant={ButtonVariant.PRIMARY}
          on_click={handlePurchase}
          is_loading={loading}
          disabled={!canPurchase}
          aria_label={`Buy ${item.name}`}
        />
      </div>
      {renderValidationError()}
      {renderApiError()}
      {renderSuccessMessage()}
    </div>
  );
};

export default GoblinShopPurchaseForm;
