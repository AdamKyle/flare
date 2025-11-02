import React, { ReactNode } from 'react';

import CharacterReincarnationProps from './types/character-reincarnation-props';
import { formatNumberWithCommas } from '../../util/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const CharacterReincarnation = ({
  reincarnation_info,
}: CharacterReincarnationProps): ReactNode => {
  const linkLabel = (): ReactNode => {
    return (
      <span>
        Learn more here <i className="fas fa-external-link-alt"></i>
      </span>
    );
  };

  return (
    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
      <div>
        <Dl>
          <Dt>Times Reincarnated:</Dt>
          <Dd>{reincarnation_info.reincarnated_times}</Dd>
          <Dt>Stat Bonus (pts.):</Dt>
          <Dd>
            {formatNumberWithCommas(
              reincarnation_info.reincarnated_stat_increase
            )}
          </Dd>
          <Dt>Base Stat Mod:</Dt>
          <Dd>{(reincarnation_info.base_damage_stat_mod * 100).toFixed(2)}%</Dd>
          <Dt>base Damage Mod:</Dt>
          <Dd>{(reincarnation_info.base_damage_stat_mod * 100).toFixed(2)}%</Dd>
          <Dt>XP Penalty:</Dt>
          <Dd>{(reincarnation_info.xp_penalty * 100).toFixed(2)}%</Dd>
        </Dl>
      </div>
      <div>
        <Alert variant={AlertVariant.INFO}>
          <p className={'my-2'}>
            You must be max level (5,000) to reincarnate and have 50,000 Copper
            Coins.
          </p>
          <p className={'my-2'}>
            Reincarnation sets your level back to level 1. You keep all your
            skills, equipment, everything. You will also gain a % of your
            current stats added to your raw stats each time you reincarnate
            making your character stronger over time.
          </p>
          <p className={'my-2'}>
            <LinkButton
              variant={ButtonVariant.PRIMARY}
              label={linkLabel()}
              on_click={() => {}}
              additional_css={'dark:text-gray-300'}
              aria_label={'Learn more link'}
            />
          </p>
        </Alert>
        <div>
          <Button
            on_click={() => {}}
            label={'Reincarnate'}
            variant={ButtonVariant.PRIMARY}
            disabled={true}
            additional_css={'w-full my-2'}
          />
        </div>
      </div>
    </div>
  );
};

export default CharacterReincarnation;
