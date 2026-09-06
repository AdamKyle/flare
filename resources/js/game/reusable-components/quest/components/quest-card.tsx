import React, { ReactNode } from 'react';

import {
  QUEST_TREE_STATE_ICON,
  QUEST_TREE_STATE_LABELS,
  QUEST_TREE_STATE_SHORT_LABELS,
} from '../enums/quest-tree-state';
import {
  questCardBaseStyles,
  questCardBorderStyles,
  questCardFocusRingStyles,
  questCardPrimaryTextStyles,
  questCardSecondaryTextStyles,
  questCardThemeStyles,
} from '../styles/quest-card-styles';
import QuestCardProps from '../types/quest-card-props';

/**
 * Canonical, permission-neutral Quest card: the Quest-domain counterpart to
 * the inventory Item card, sharing its density and shadow quality but using
 * the `glacier` palette. Renders as a single full-card `button` when
 * `on_open_quest` is supplied — every interactive consumer supplies no
 * nested interactive controls, so the entire card is one large
 * click/keyboard target rather than only its title text — or as a
 * semantically meaningful noninteractive `<article>` when no navigation
 * callback is supplied, for permission-neutral factual contexts with no
 * navigation available. Used by the desktop-tree-adjacent relationship
 * cards, the flattened mobile Quest list, and the Required Quest/Required
 * Quest Chain dependency cards, and is safe for a future Player adapter to
 * reuse as-is by supplying `state` from the player's completed Quest ids.
 */
const QuestCard = ({
  quest_id: questId,
  name,
  kind_label: kindLabel,
  state,
  npc_name: npcName,
  child_count: childCount,
  context_label: contextLabel,
  on_open_quest: onOpenQuest,
}: QuestCardProps): ReactNode => {
  const renderState = (): ReactNode => {
    if (!state) {
      return null;
    }

    return (
      <span
        className={`flex items-center gap-1 text-xs font-medium ${questCardSecondaryTextStyles()}`}
      >
        <span aria-hidden="true">{QUEST_TREE_STATE_ICON[state]}</span>
        <span>{QUEST_TREE_STATE_SHORT_LABELS[state]}</span>
        <span className="sr-only">{QUEST_TREE_STATE_LABELS[state]}</span>
      </span>
    );
  };

  const renderMeta = (): ReactNode => {
    const hasMeta =
      Boolean(contextLabel) ||
      Boolean(npcName) ||
      (typeof childCount === 'number' && childCount > 0);

    if (!hasMeta) {
      return null;
    }

    return (
      <div
        className={`flex flex-col gap-0.5 text-xs ${questCardSecondaryTextStyles()}`}
      >
        {contextLabel && <span>{contextLabel}</span>}
        {npcName && <span>Quest Giver: {npcName}</span>}
        {typeof childCount === 'number' && childCount > 0 && (
          <span>
            {childCount} {childCount === 1 ? 'child Quest' : 'child Quests'}
          </span>
        )}
      </div>
    );
  };

  const renderCardContent = (): ReactNode => (
    <>
      <i
        className={`ra ra-scroll-unfurled text-2xl ${questCardSecondaryTextStyles()}`}
        aria-hidden="true"
      />
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        <span
          className={`text-sm font-semibold break-words ${questCardPrimaryTextStyles()}`}
        >
          {name}
        </span>
        {kindLabel && (
          <span className={`text-xs ${questCardSecondaryTextStyles()}`}>
            {kindLabel}
          </span>
        )}
        {renderState()}
        {renderMeta()}
      </div>
    </>
  );

  if (onOpenQuest) {
    return (
      <button
        type="button"
        onClick={() => onOpenQuest(questId)}
        aria-label={`Open Quest details for ${name}`}
        className={`${questCardBaseStyles()} ${questCardThemeStyles()} ${questCardBorderStyles(state)} ${questCardFocusRingStyles()}`}
      >
        {renderCardContent()}
      </button>
    );
  }

  return (
    <article
      aria-label={name}
      className={`${questCardBaseStyles()} ${questCardThemeStyles()} ${questCardBorderStyles(state)}`}
    >
      {renderCardContent()}
    </article>
  );
};

export default QuestCard;
