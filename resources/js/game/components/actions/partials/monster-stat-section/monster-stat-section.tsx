import React, { ReactNode } from 'react';

import { useManageMonsterStatSectionVisibility } from './hooks/use-manage-monster-stat-section-visibility';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

export const MonsterStatSection = (): ReactNode => {
  const { closeMonsterStats } = useManageMonsterStatSectionVisibility();

  return (
    <ContainerWithTitle
      manageSectionVisibility={closeMonsterStats}
      title={'Monster Name'}
    >
      <Card>
        <div role="region" aria-labelledby="celestial-info" className="mb-6">
          <h2 id="celestial-info" className="sr-only">
            Celestial Creature Information
          </h2>
          <Alert variant={AlertVariant.INFO}>
            <strong>This Creature is a celestial</strong>: You will find the
            conjuration cost in shards to conjure this beast. You cannot
            encounter this beast in the wild unless you trigger a spawn by
            either moving (small chance) or unless it's Celestial Day, in which
            case any form of movement has an 80% chance to conjure one. Players
            who completed Quest X can use /PCT command to instantly travel to
            it. Killing it in one hit is advised or it will move and heal for
            full health.
          </Alert>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
          <div>
            <Dl>
              <Dt>Monster Name:</Dt>
              <Dd>Name</Dd>
              <Dt>Receive 1/3 Xp at level:</Dt>
              <Dd>10</Dd>
              <Dt>Conjuration Cost (Shards):</Dt>
              <Dd>1,000</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Basic Stats
            </h3>
            <Separator />
            <Dl>
              <Dt>Health Range:</Dt>
              <Dd>100 - 200</Dd>
              <Dt>Attack Range:</Dt>
              <Dd>100 - 200</Dd>
              <Dt>Max Spell Damage:</Dt>
              <Dd>350</Dd>
              <Dt>Entrancing Chance:</Dt>
              <Dd>8%</Dd>
              <Dt>Armour Class (Defence):</Dt>
              <Dd>150</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Ambush and Counter
            </h3>
            <Separator />
            <Dl>
              <Dt>Ambush Chance:</Dt>
              <Dd>8%</Dd>
              <Dt>Ambush Resistance:</Dt>
              <Dd>2%</Dd>
              <Dt>Counter Chance:</Dt>
              <Dd>10%</Dd>
              <Dt>Counter Resistance:</Dt>
              <Dd>10%</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Rewards
            </h3>
            <Separator />
            <Dl>
              <Dt>XP:</Dt>
              <Dd>100</Dd>
              <Dt>Gold:</Dt>
              <Dd>500</Dd>
              <Dt>Drop Chance:</Dt>
              <Dd>10%</Dd>
            </Dl>
          </div>

          <div>
            <h3 className="text-danube-500 dark:text-danube-700">Core Stats</h3>
            <Separator />
            <Dl>
              <Dt>Str:</Dt>
              <Dd>100</Dd>
              <Dt>Dex:</Dt>
              <Dd>100</Dd>
              <Dt>Int:</Dt>
              <Dd>100</Dd>
              <Dt>Chr:</Dt>
              <Dd>100</Dd>
              <Dt>Agi:</Dt>
              <Dd>100</Dd>
              <Dt>Focus:</Dt>
              <Dd>100</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Skills
            </h3>
            <Separator />
            <Dl>
              <Dt>Accuracy:</Dt>
              <Dd>1.5%</Dd>
              <Dt>Casting Accuracy:</Dt>
              <Dd>2.8%</Dd>
              <Dt>Dodge:</Dt>
              <Dd>10%</Dd>
              <Dt>Criticality:</Dt>
              <Dd>10%</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Resistances
            </h3>
            <Separator />
            <Dl>
              <Dt>Affix:</Dt>
              <Dd>1.5%</Dd>
              <Dt>Spells:</Dt>
              <Dd>2.8%</Dd>
              <Dt>Life Stealing:</Dt>
              <Dd>10%</Dd>
            </Dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Devouring Light / Darkness
            </h3>
            <Separator />
            <Dl>
              <Dt>Devouring Light:</Dt>
              <Dd>1.5%</Dd>
              <Dt>Devouring Darkness:</Dt>
              <Dd>2.8%</Dd>
            </Dl>
          </div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};
