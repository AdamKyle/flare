import React, { ReactNode } from 'react';

import GemTierCostSummaryProps from './types/gem-tier-cost-summary-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

const tierRoles: Record<number, string> = {
  1: 'Gem Ability + two raw stats',
  2: 'Raw stats and direct combat modifiers',
  3: 'Class Rank, Class Mastery, Weapon Mastery, and class-skill specialization',
  4: 'One elemental atonement + two reward, penetration, or progression modifiers',
};

const GemTierCostSummary = ({
  tier,
  tierNumber,
}: GemTierCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
    <dt>Tier role</dt>
    <dd>{tierRoles[tierNumber]}</dd>
    {tierNumber === 4 ? (
      <>
        <dt>Elemental rule</dt>
        <dd>Penetration always matches the Gem&apos;s atonement.</dd>
      </>
    ) : null}
    <dt>Gold Dust</dt>
    <dd>
      <CurrencyDisplay
        currency={CurrencyType.GOLD_DUST}
        amount={tier.cost.gold_dust}
        display_mode={CurrencyDisplayMode.EXACT}
        show_label={false}
      />
    </dd>
    <dt>Shards</dt>
    <dd>
      <CurrencyDisplay
        currency={CurrencyType.SHARDS}
        amount={tier.cost.shards}
        display_mode={CurrencyDisplayMode.EXACT}
        show_label={false}
      />
    </dd>
    <dt>Copper Coins</dt>
    <dd>
      <CurrencyDisplay
        currency={CurrencyType.COPPER_COINS}
        amount={tier.cost.copper_coins}
        display_mode={CurrencyDisplayMode.EXACT}
        show_label={false}
      />
    </dd>
    <dt>Skill level range</dt>
    <dd>
      {tier.min_level}–{tier.max_level}
    </dd>
    <dt>Success chance (fraction)</dt>
    <dd>{tier.chance}</dd>
  </dl>
);

export default GemTierCostSummary;
