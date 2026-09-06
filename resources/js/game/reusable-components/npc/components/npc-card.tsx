import React, { ReactNode } from 'react';

import {
  npcCardBaseStyles,
  npcCardFocusRingStyles,
  npcCardIconStyles,
  npcCardPrimaryTextStyles,
  npcCardSecondaryTextStyles,
  npcCardThemeStyles,
} from '../styles/npc-card-styles';
import NpcCardProps from '../types/npc-card-props';

/**
 * Canonical, permission-neutral Terracotta NPC relationship card: showing
 * NPC name, optional Type label, coordinates when both X and Y are
 * available, and an optional Game Map name. Renders as a single full-width
 * interactive `button` when `on_open_npc` is supplied, or as a semantically
 * meaningful noninteractive `<article>` when no navigation callback is
 * supplied, for permission-neutral factual contexts with no navigation
 * available — matching the canonical `LocationCard` pattern.
 */
const NpcCard = ({
  npc_id: npcId,
  name,
  type_label: typeLabel,
  x,
  y,
  game_map_name: gameMapName,
  on_open_npc: onOpenNpc,
}: NpcCardProps): ReactNode => {
  const hasCoordinates = typeof x === 'number' && typeof y === 'number';

  const renderMeta = (): ReactNode => {
    const hasMeta =
      Boolean(typeLabel) || hasCoordinates || Boolean(gameMapName);

    if (!hasMeta) {
      return null;
    }

    return (
      <div
        className={`flex flex-col gap-0.5 text-xs ${npcCardSecondaryTextStyles()}`}
      >
        {typeLabel && <span>{typeLabel}</span>}
        {hasCoordinates && (
          <span>
            X {x}, Y {y}
          </span>
        )}
        {gameMapName && <span>Map: {gameMapName}</span>}
      </div>
    );
  };

  const renderCardContent = (): ReactNode => (
    <>
      <i
        className={`fas fa-user text-2xl ${npcCardIconStyles()}`}
        aria-hidden="true"
      />
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        <span
          className={`text-sm font-semibold break-words ${npcCardPrimaryTextStyles()}`}
        >
          {name}
        </span>
        {renderMeta()}
      </div>
    </>
  );

  if (onOpenNpc) {
    return (
      <button
        type="button"
        onClick={() => onOpenNpc(npcId)}
        aria-label={`Open NPC details for ${name}`}
        className={`${npcCardBaseStyles()} ${npcCardThemeStyles()} ${npcCardFocusRingStyles()}`}
      >
        {renderCardContent()}
      </button>
    );
  }

  return (
    <article
      aria-label={name}
      className={`${npcCardBaseStyles()} ${npcCardThemeStyles()}`}
    >
      {renderCardContent()}
    </article>
  );
};

export default NpcCard;
