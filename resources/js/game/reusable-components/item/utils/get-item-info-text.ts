import { match } from 'ts-pattern';

import { ItemDetailsSectionLabels } from '../enums/item-details-section-labels';

export const getItemInfoText = (
  label: string,
  display: string,
  itemType: string
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
    .with(
      ItemDetailsSectionLabels.DAMAGE,
      () => `Increases damage by ${display}.`
    )
    .with(
      ItemDetailsSectionLabels.AC,
      () => `Increases AC (armour) by ${display}.`
    )
    .otherwise(
      () => `This raises the character’s ${label.toLowerCase()} by ${display}.`
    );
