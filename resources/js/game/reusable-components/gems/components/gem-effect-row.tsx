import clsx from 'clsx';
import React, { ReactNode } from 'react';

import GemEffectRowProps from '../types/gem-effect-row-props';
import {
  GemEffectPolarity,
  resolveGemEffectPolarity,
} from '../utils/gem-effect-polarity';

import { formatSignedPercent } from 'game-utils/format-number';

/**
 * One shared, factual Gem modifier row. The chevron always points up because
 * the underlying value always increases; its color communicates whether that
 * increase is beneficial or harmful, since color alone is never the only
 * signal — the accessible text below spells it out too.
 */
const GemEffectRow = ({
  field,
  label,
  value,
}: GemEffectRowProps): ReactNode => {
  const polarity = resolveGemEffectPolarity(field);
  const isBeneficial = polarity === GemEffectPolarity.BENEFICIAL;

  const colorClassName = isBeneficial
    ? 'text-emerald-600 dark:text-emerald-400'
    : 'text-rose-600 dark:text-rose-400';

  const accessibleExplanation = isBeneficial
    ? 'beneficial modifier'
    : 'increased enemy difficulty';

  return (
    <div className="flex items-center justify-between gap-2 py-1 text-sm">
      <span className="text-gray-700 dark:text-gray-300">{label}</span>
      <span
        className={clsx(
          'flex items-center gap-1 font-medium tabular-nums',
          colorClassName
        )}
      >
        <i className="fas fa-chevron-up" aria-hidden="true" />
        <span aria-hidden="true">{formatSignedPercent(value)}</span>
        <span className="sr-only">
          {formatSignedPercent(value)} {label}, {accessibleExplanation}
        </span>
      </span>
    </div>
  );
};

export default GemEffectRow;
