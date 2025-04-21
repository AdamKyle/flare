import React, { ReactNode } from 'react';

import CharacterReincarnationProps from './types/character-reincarnation-props';
import { formatNumberWithCommas } from '../../util/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';

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
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <dl>
          <dt>Times Reincarnated:</dt>
          <dd>{reincarnation_info.reincarnated_times}</dd>
          <dt>Stat Bonus (pts.):</dt>
          <dd>
            {formatNumberWithCommas(
              reincarnation_info.reincarnated_stat_increase
            )}
          </dd>
          <dt>Base Stat Mod:</dt>
          <dd>{(reincarnation_info.base_damage_stat_mod * 100).toFixed(2)}%</dd>
          <dt>base Damage Mod:</dt>
          <dd>{(reincarnation_info.base_damage_stat_mod * 100).toFixed(2)}%</dd>
          <dt>XP Penalty:</dt>
          <dd>{(reincarnation_info.xp_penalty * 100).toFixed(2)}%</dd>
        </dl>
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
