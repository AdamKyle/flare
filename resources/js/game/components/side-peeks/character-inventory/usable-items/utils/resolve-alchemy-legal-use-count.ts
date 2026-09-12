import BaseUsableItemDefinition from '../../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import ActiveBoonDefinition from '../../../../actions/partials/floating-cards/crafting-section/sections/alchemy/active-boons/api/definitions/active-boon-definition';

const MAX_BOON_USES = 10;
const MAX_DURATION_MINUTES = 8 * 60;

export const isSelfUseAlchemyBoonItem = (
  item: BaseUsableItemDefinition
): boolean =>
  item.usable &&
  item.holy_level === null &&
  !item.damages_kingdoms &&
  item.gem_scroll_type === null &&
  (item.lasts_for ?? 0) > 0;

export const resolveAlchemyLegalUseCount = (
  item: BaseUsableItemDefinition,
  activeBoons: ActiveBoonDefinition[],
  now: Date
): number => {
  if (!isSelfUseAlchemyBoonItem(item)) {
    return 0;
  }

  const lastsFor = item.lasts_for ?? 0;
  const matchingBoon = activeBoons.find(
    (boon) => boon.item_id === item.item_id
  );

  const currentTotalUses = activeBoons.reduce(
    (sum, boon) => sum + boon.amount_used,
    0
  );

  const remainingOverallUses = MAX_BOON_USES - currentTotalUses;

  if (!item.can_stack) {
    if (matchingBoon) {
      return 0;
    }

    return Math.max(0, Math.min(1, item.amount, remainingOverallUses));
  }

  const remainingMinutesOnMatchingBoon = matchingBoon
    ? Math.max(
        0,
        Math.ceil(
          (new Date(matchingBoon.complete).getTime() - now.getTime()) / 60000
        )
      )
    : 0;

  const remainingDurationMinutes = Math.max(
    0,
    MAX_DURATION_MINUTES - remainingMinutesOnMatchingBoon
  );

  const durationUses = Math.ceil(remainingDurationMinutes / lastsFor);

  return Math.max(0, Math.min(item.amount, remainingOverallUses, durationUses));
};
