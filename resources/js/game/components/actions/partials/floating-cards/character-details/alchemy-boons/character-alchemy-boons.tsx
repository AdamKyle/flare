import React, { ReactNode } from 'react';

import CharacterAlchemyBoonsProps from './types/character-alchemy-boons-props';
import ActiveBoonDefinition from '../../crafting-section/sections/alchemy/active-boons/api/definitions/active-boon-definition';

import CountdownProgressButton from 'ui/buttons/countdown-progress-button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const resolveAggregateWindow = (
  boons: Pick<ActiveBoonDefinition, 'started' | 'complete'>[]
): { started_at: string; complete_at: string } | null => {
  if (boons.length === 0) {
    return null;
  }

  const startedAt = boons.reduce(
    (earliest, boon) => (boon.started < earliest ? boon.started : earliest),
    boons[0].started
  );

  const completeAt = boons.reduce(
    (latest, boon) => (boon.complete > latest ? boon.complete : latest),
    boons[0].complete
  );

  return { started_at: startedAt, complete_at: completeAt };
};

const CharacterAlchemyBoons = (
  props: CharacterAlchemyBoonsProps
): ReactNode => {
  if (props.loading || props.boons.length === 0) {
    return null;
  }

  const aggregateWindow = resolveAggregateWindow(props.boons);

  if (!aggregateWindow) {
    return null;
  }

  return (
    <CountdownProgressButton
      started_at={aggregateWindow.started_at}
      complete_at={aggregateWindow.complete_at}
      label_prefix="Alchemy Boons"
      variant={ButtonVariant.ALCHEMY}
      additional_css="w-full mt-2"
      on_click={props.on_open}
      on_complete={props.on_complete}
      detailed_time
    />
  );
};

export default CharacterAlchemyBoons;
