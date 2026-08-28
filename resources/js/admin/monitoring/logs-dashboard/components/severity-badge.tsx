import React from 'react';

import SeverityBadgeProps from '../types/severity-badge-props';
import { SEVERITY_COLORS } from '../values/severity-colors';

export default function SeverityBadge({ severity }: SeverityBadgeProps) {
  const cls =
    SEVERITY_COLORS[severity.toLowerCase()] ?? SEVERITY_COLORS.unknown;

  return (
    <span
      className={`inline-block rounded px-1.5 py-0.5 text-xs font-semibold uppercase ${cls}`}
    >
      {severity}
    </span>
  );
}
