import { match } from 'ts-pattern';

import { ItemDetailsSectionLabels } from '../enums/item-details-section-labels';

export const getItemInfoText = (
  label: string,
  display: string,
  itemType: string,
  rawValue: number
): string =>
  match(label)
    .with(ItemDetailsSectionLabels.COST, () => `This costs ${display} gold.`)
    .with(
      ItemDetailsSectionLabels.CRAFTING_REQ,
      () =>
        `Requires ${itemType.toLowerCase()} crafting skill at level: ${display}.`
    )
    .with(
      ItemDetailsSectionLabels.CRAFTING_TRIVIAL,
      () => `${itemType} crafting becomes trivial at level: ${display}.`
    )
    .with(ItemDetailsSectionLabels.DAMAGE, () =>
      rawValue < 0
        ? `Decreases damage by ${display}.`
        : `Increases damage by ${display}.`
    )
    .with(ItemDetailsSectionLabels.AC, () =>
      rawValue < 0
        ? `Decreases AC (armour) by ${display}.`
        : `Increases AC (armour) by ${display}.`
    )
    .with(ItemDetailsSectionLabels.COUNTER, () =>
      rawValue < 0
        ? `Decreases counter chance by ${display}.`
        : `Increases counter chance by ${display}.`
    )
    .with(ItemDetailsSectionLabels.COUNTER_RESISTANCE, () =>
      rawValue < 0
        ? `Decreases counter resistance chance by ${display}.`
        : `Increases counter resistance chance by ${display}.`
    )
    .with(ItemDetailsSectionLabels.AMBUSH, () =>
      rawValue < 0
        ? `Decreases Ambush chance by ${display}.`
        : `Increases ambush chance by ${display}.`
    )
    .with(ItemDetailsSectionLabels.AMBUSH_RESISTANCE, () =>
      rawValue < 0
        ? `Decreases ambush resistance chance by ${display}.`
        : `Increases ambush resistance chance by ${display}.`
    )
    .otherwise(() =>
      rawValue < 0
        ? `This decreases ${label.toLowerCase()} by ${display}.`
        : `This raises ${label.toLowerCase()} by ${display}.`
    );
