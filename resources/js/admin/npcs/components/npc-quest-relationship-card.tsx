import React, { ReactNode } from 'react';

import NpcQuestRelationshipCardProps from './types/npc-quest-relationship-card-props';
import {
  questCardBaseStyles,
  questCardBorderStyles,
  questCardFocusRingStyles,
  questCardPrimaryTextStyles,
  questCardSecondaryTextStyles,
  questCardThemeStyles,
} from '../../../game/reusable-components/quest/styles/quest-card-styles';
import { NpcQuestRelatedItemDefinition } from '../api/definitions/npc-quest-definition';

/**
 * NPC Quest relationship card: the Quest-domain Glacier card visual
 * language, showing the Quest this NPC gives alongside its Required,
 * Secondary, and Reward Item identities. The Quest name and each Item
 * identity are independent clickable controls (never nested inside one
 * another) so a screen reader user can activate any one of them directly.
 */
const NpcQuestRelationshipCard = ({
  quest,
  on_open_quest: onOpenQuest,
  on_open_item: onOpenItem,
}: NpcQuestRelationshipCardProps): ReactNode => {
  const renderItemLink = (
    label: string,
    item: NpcQuestRelatedItemDefinition | null
  ): ReactNode => {
    if (!item) {
      return null;
    }

    return (
      <span className="flex items-center gap-1">
        <span>{label}:</span>
        <button
          type="button"
          onClick={() => onOpenItem(item.id, item.name)}
          className={`text-danube-700 hover:text-danube-600 decoration-danube-400 font-medium underline underline-offset-2 ${questCardFocusRingStyles()}`}
        >
          {item.name}
        </button>
      </span>
    );
  };

  const renderQuestAction = (): ReactNode => {
    const content = (
      <>
        <i
          className={`ra ra-scroll-unfurled text-2xl ${questCardSecondaryTextStyles()}`}
          aria-hidden="true"
        />
        <span
          className={`text-sm font-semibold break-words ${questCardPrimaryTextStyles()}`}
        >
          {quest.name}
        </span>
      </>
    );

    if (!onOpenQuest) {
      return <div className="flex w-full items-center gap-2">{content}</div>;
    }

    return (
      <button
        type="button"
        onClick={() => onOpenQuest(quest.id)}
        aria-label={`Open Quest details for ${quest.name}`}
        className={`flex w-full items-center gap-2 text-left ${questCardFocusRingStyles()}`}
      >
        {content}
      </button>
    );
  };

  return (
    <article
      className={`${questCardBaseStyles()} ${questCardThemeStyles()} ${questCardBorderStyles()}`}
    >
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        {renderQuestAction()}

        <div
          className={`flex flex-wrap gap-x-4 gap-y-1 text-xs ${questCardSecondaryTextStyles()}`}
        >
          {renderItemLink('Required', quest.required_item)}
          {renderItemLink('Secondary', quest.secondary_required_item)}
          {renderItemLink('Reward', quest.reward_item)}
        </div>
      </div>
    </article>
  );
};

export default NpcQuestRelationshipCard;
