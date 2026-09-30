import clsx from 'clsx';
import React, { ReactNode } from 'react';

import GemTierCostSummaryProps from './types/gem-tier-cost-summary-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

import { formatPercent } from 'game-utils/format-number';

import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const tierRoles: Record<number, string> = {
  1: 'Gem Ability + two raw stats',
  2: 'Raw stats and direct combat modifiers',
  3: 'Class Rank, Class Mastery, Weapon Mastery, and class-skill specialization',
  4: 'One elemental atonement + two reward, penetration, or progression modifiers',
};

const GemTierCostSummary = ({
  tier,
  tierNumber,
  on_open_details: onOpenDetails,
}: GemTierCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
    <dt>Tier role</dt>
    <dd>
      <button
        type="button"
        onClick={onOpenDetails}
        className="text-danube-700 hover:text-danube-600 focus-visible:ring-danube-500 dark:text-danube-300 dark:hover:text-danube-200 inline-flex items-center gap-2 rounded-sm text-right font-medium underline underline-offset-2 focus:outline-none focus-visible:ring-2"
      >
        {tierRoles[tierNumber]}
        <i className="fas fa-chevron-right text-xs" aria-hidden="true" />
      </button>
    </dd>
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
    <dt className="flex items-center gap-1">
      Success chance
      <GeneralToolTip
        label="Gem crafting success chance"
        message={`This is the chance that the craft succeeds. Higher Gem tiers begin with a lower base chance (${formatPercent(tier.base_chance, 0)} for this tier). Your Gem Crafting skill bonus raises the effective chance, up to 100%. A maximum-level Gem Crafting skill succeeds automatically.`}
        placement="auto"
      />
    </dt>
    <dd
      className={clsx('font-semibold tabular-nums', {
        'text-emerald-700 dark:text-emerald-400': tier.chance >= 0.75,
        'text-amber-700 dark:text-amber-400':
          tier.chance >= 0.5 && tier.chance < 0.75,
        'text-rose-700 dark:text-rose-400': tier.chance < 0.5,
      })}
    >
      {formatPercent(tier.chance, 0)}
    </dd>
  </dl>
);

export default GemTierCostSummary;
