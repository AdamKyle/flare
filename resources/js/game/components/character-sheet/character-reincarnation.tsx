import React, { ReactNode } from 'react';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CharacterReincarnation = (): ReactNode => {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <dl>
          <dt>Times Reincarnated:</dt>
          <dd>50</dd>
          <dt>Stat Bonus (pts.):</dt>
          <dd>50,000,000,000</dd>
          <dt>Base Stat Mod:</dt>
          <dd>50%</dd>
          <dt>base Damage Mod:</dt>
          <dd>45%</dd>
          <dt>XP Penalty:</dt>
          <dd>465%</dd>
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
          <p className={'my-2'}>Learn More Here.</p>
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
