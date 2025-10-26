import React from 'react';

import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import StatToolTip from '../../../reusable-components/item/tool-tips/stat-tool-tip';
import { getCraftingLabelForType } from '../../../reusable-components/item/utils/item-view';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

type ShopItemBaseViewProps = {
  item: EquippableItemWithBase;
};

const ShopItemBaseView = ({ item }: ShopItemBaseViewProps) => {
  const craftingLabel = getCraftingLabelForType(item.type);

  return (
    <div>
      <h3 className="mb-1 text-sm font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Cost
      </h3>
      <Separator />
      <Dl>
        <Dt>Cost</Dt>
        <Dd>
          <span className="font-medium">
            {formatNumberWithCommas(item.cost)} gold
          </span>
        </Dd>
      </Dl>
      <Separator />
      <h3 className="mb-1 text-sm font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Crafting Details
      </h3>
      <Separator />
      <Dl>
        <Dt>Crafting Type</Dt>
        <Dd>
          <span className="font-medium">{craftingLabel}</span>
        </Dd>

        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatToolTip
              label={`Indicates the level the skill: ${craftingLabel} has to be at for you to be able to craft this item.`}
              value={Number(item.skill_level_req)}
              align="left"
              size="sm"
              custom_message
            />
            <span>Level Required</span>
          </span>{' '}
        </Dt>
        <Dd>
          <span className="font-medium">
            {formatNumberWithCommas(item.skill_level_req)}
          </span>
        </Dd>

        <Dt>
          <span className="inline-flex items-center gap-2">
            <StatToolTip
              label="Crafting XP gain threshold (you stop gaining XP at this level)"
              value={Number(item.skill_level_trivial)}
              align="right"
              size="sm"
              custom_message
            />
            <span>Level Trivial</span>
          </span>
        </Dt>
        <Dd>
          <span className="font-medium">
            {formatNumberWithCommas(item.skill_level_trivial)}
          </span>
        </Dd>
      </Dl>
    </div>
  );
};

export default ShopItemBaseView;
