import { CurrencyType } from '../../../../reusable-components/currency/enums/currency-type';
import SpinSlotsResponseDefinition from '../api/definitions/spin-slots-response-definition';

export const buildSlotCurrencyUpdate = (
  result: SpinSlotsResponseDefinition
): Partial<Record<CurrencyType, number>> => {
  const currencyUpdate: Partial<Record<CurrencyType, number>> = {
    [CurrencyType.GOLD]: result.gold,
  };

  if (result.reward) {
    currencyUpdate[result.reward.currency] = result.reward.balance;
  }

  return currencyUpdate;
};
