import { CURRENCY_PRESENTATION } from '../../../../reusable-components/currency/constants/currency-presentation';
import { CurrencyType } from '../../../../reusable-components/currency/enums/currency-type';
import { SlotSymbolType } from '../enums/slot-symbol-type';
import SlotSymbolDefinition from '../types/slot-symbol-definition';
import SlotSymbolPresentation from '../types/slot-symbol-presentation';

const SLOT_SYMBOL_CURRENCIES: Partial<Record<number, CurrencyType>> = {
  [SlotSymbolType.GOLD_DUST]: CurrencyType.GOLD_DUST,
  [SlotSymbolType.SHARDS]: CurrencyType.SHARDS,
  [SlotSymbolType.COPPER_COINS]: CurrencyType.COPPER_COINS,
};

const SLOT_SYMBOL_COLOR_CLASSES: Partial<Record<number, string>> = {
  [SlotSymbolType.APPLE]: 'text-rose-600 dark:text-rose-400',
  [SlotSymbolType.SEEDLING]: 'text-emerald-600 dark:text-emerald-400',
  [SlotSymbolType.CARROT]: 'text-danube-600 dark:text-danube-300',
};

const DEFAULT_SYMBOL_COLOR_CLASS = 'text-gray-700 dark:text-gray-300';

export const resolveSlotSymbolPresentation = (
  symbol: SlotSymbolDefinition
): SlotSymbolPresentation => {
  const currency = SLOT_SYMBOL_CURRENCIES[symbol.type];

  if (currency) {
    const currencyPresentation = CURRENCY_PRESENTATION[currency];

    return {
      key: `${symbol.type}`,
      label: currencyPresentation.label,
      icon_class: currencyPresentation.icon_class,
      color_class: currencyPresentation.color_class,
    };
  }

  return {
    key: `${symbol.type}`,
    label: symbol.title,
    icon_class: symbol.icon,
    color_class:
      SLOT_SYMBOL_COLOR_CLASSES[symbol.type] ?? DEFAULT_SYMBOL_COLOR_CLASS,
  };
};
