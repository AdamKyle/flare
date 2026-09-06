import React, { ReactNode } from 'react';

import GameMapKingdomColorSwatchProps from './types/game-map-kingdom-color-swatch-props';

/**
 * Compact visible swatch for a Game Map's persisted Kingdom color. Shows
 * the actual color rather than printing its hexadecimal value; the
 * hexadecimal value remains available to assistive technology through the
 * accessible name. `backgroundColor` is set inline because the value is
 * dynamic persisted runtime data with no static Tailwind class equivalent.
 */
const GameMapKingdomColorSwatch = ({
  color,
}: GameMapKingdomColorSwatchProps): ReactNode => (
  <span
    role="img"
    aria-label={`Kingdom color ${color}`}
    style={{ backgroundColor: color }}
    className="border-glacier-300 dark:border-glacier-700 inline-block h-6 w-12 rounded-md border"
  />
);

export default GameMapKingdomColorSwatch;
