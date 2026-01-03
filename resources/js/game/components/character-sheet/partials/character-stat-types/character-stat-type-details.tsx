import { isNil } from 'lodash';
import React, { ReactNode } from 'react';

import { CharacterStatBreakDownUrls } from './api/enums/character-stat-break-down-urls';
import { useGetCharacterStatBreakDown } from './api/hooks/use-get-character-stat-break-down';
import EquippedItems from './partials/equipped-items';
import CharacterStatTypeDetailsProps from './types/character-stat-type-details-props';

import { GameDataError } from 'game-data/components/game-data-error';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

export const CharacterStatTypeDetails = ({
  stat_type,
  character_id,
}: CharacterStatTypeDetailsProps): ReactNode => {
  const { data, loading, error } = useGetCharacterStatBreakDown({
    url: CharacterStatBreakDownUrls.CHARACTER_STAT_BREAKDOWN,
    urlParams: { character: character_id },
    statType: stat_type,
  });

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error !== null) {
    return (
      <Alert variant={AlertVariant.DANGER}>
        <p>{error.message}</p>
      </Alert>
    );
  }

  if (data === null || isNil(stat_type)) {
    return <GameDataError />;
  }

  const renderMapReduction = () => {
    if (!data.map_reduction) {
      return;
    }

    return (
      <li className={'text-rose-700 dark:text-rose-500'}>
        You are on <strong>{data.map_reduction.map_name}</strong> and thus
        suffer a {(data.map_reduction.reduction_amount * 100).toFixed(2)}%
        reduction to all stats, making you feel weaker.
      </li>
    );
  };

  return (
    <div>
      <div className={'mx-auto w-full md:w-2/3'}>
        <Dl>
          <Dt>
            <strong>Base Stat Value</strong>:
          </Dt>
          <Dd>{formatNumberWithCommas(data.base_value)}</Dd>
          <Dt>
            <strong>Modded Stat Value</strong>:
          </Dt>
          <Dd>
            {formatNumberWithCommas(parseInt(data.modded_value.toFixed(0)))}
          </Dd>
        </Dl>
        <p className="my-4">
          Below is a break down of everything that takes your base stat value
          and makes it your modded stat value. When it comes to fighting, we
          only care about your modded stat value. If you become voided, we use
          your raw stat value. Finally your raw stat value has a percentage of
          it carried over and added to your raw stat value when you reincarnate.
        </p>
        <Separator />
      </div>
      <div className={'w-full'}>
        <div className={'grid-cols-0 grid gap-2 md:grid-cols-2'}>
          <div>
            <div className={'text-center'}>
              <h4>Gear affecting this stat</h4>
            </div>
            <Separator />
            <ol className="list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
              <EquippedItems
                items_equipped={data.items_equipped}
                stat_type={stat_type}
              />
            </ol>
          </div>
          <div>
            <div className={'text-center'}>
              <h4>Other Enhancements/Afflictions</h4>
            </div>
            <Separator />
            <ol className="list-inside list-decimal space-y-4 text-gray-500 dark:text-gray-400">
              {renderMapReduction()}
            </ol>
          </div>
        </div>
      </div>
    </div>
  );
};
