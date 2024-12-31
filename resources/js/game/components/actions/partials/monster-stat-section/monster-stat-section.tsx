import React, { ReactNode } from 'react';

import { useManageMonsterStatSectionVisibility } from './hooks/use-manage-monster-stat-section-visibility';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';
import Separator from 'ui/seperatror/separator';

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
            <dl>
              <dt className="font-semibold">Monster Name:</dt>
              <dd>Name</dd>
              <dt className="font-semibold">Receive 1/3 Xp at level:</dt>
              <dd>10</dd>
              <dt className="font-semibold">Conjuration Cost (Shards):</dt>
              <dd>1,000</dd>
            </dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Basic Stats
            </h3>
            <Separator />
            <dl>
              <dt className="font-semibold">Health Range:</dt>
              <dd>100 - 200</dd>
              <dt className="font-semibold">Attack Range:</dt>
              <dd>100 - 200</dd>
              <dt className="font-semibold">Max Spell Damage:</dt>
              <dd>350</dd>
              <dt className="font-semibold">Entrancing Chance:</dt>
              <dd>8%</dd>
              <dt className="font-semibold">Armour Class (Defence):</dt>
              <dd>150</dd>
            </dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Ambush and Counter
            </h3>
            <Separator />
            <dl>
              <dt className="font-semibold">Ambush Chance:</dt>
              <dd>8%</dd>
              <dt className="font-semibold">Ambush Resistance:</dt>
              <dd>2%</dd>
              <dt className="font-semibold">Counter Chance:</dt>
              <dd>10%</dd>
              <dt className="font-semibold">Counter Resistance:</dt>
              <dd>10%</dd>
            </dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Rewards
            </h3>
            <Separator />
            <dl>
              <dt className="font-semibold">XP:</dt>
              <dd>100</dd>
              <dt className="font-semibold">Gold:</dt>
              <dd>500</dd>
              <dt className="font-semibold">Drop Chance:</dt>
              <dd>10%</dd>
            </dl>
          </div>

          <div>
            <h3 className="text-danube-500 dark:text-danube-700">Core Stats</h3>
            <Separator />
            <dl>
              <dt className="font-semibold">Str:</dt>
              <dd>100</dd>
              <dt className="font-semibold">Dex:</dt>
              <dd>100</dd>
              <dt className="font-semibold">Int:</dt>
              <dd>100</dd>
              <dt className="font-semibold">Chr:</dt>
              <dd>100</dd>
              <dt className="font-semibold">Agi:</dt>
              <dd>100</dd>
              <dt className="font-semibold">Focus:</dt>
              <dd>100</dd>
            </dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Skills
            </h3>
            <Separator />
            <dl>
              <dt className="font-semibold">Accuracy:</dt>
              <dd>1.5%</dd>
              <dt className="font-semibold">Casting Accuracy:</dt>
              <dd>2.8%</dd>
              <dt className="font-semibold">Dodge:</dt>
              <dd>10%</dd>
              <dt className="font-semibold">Criticality:</dt>
              <dd>10%</dd>
            </dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Resistances
            </h3>
            <Separator />
            <dl>
              <dt className="font-semibold">Affix:</dt>
              <dd>1.5%</dd>
              <dt className="font-semibold">Spells:</dt>
              <dd>2.8%</dd>
              <dt className="font-semibold">Life Stealing:</dt>
              <dd>10%</dd>
            </dl>

            <h3 className="text-danube-500 dark:text-danube-700 mt-5">
              Devouring Light / Darkness
            </h3>
            <Separator />
            <dl>
              <dt className="font-semibold">Devouring Light:</dt>
              <dd>1.5%</dd>
              <dt className="font-semibold">Devouring Darkness:</dt>
              <dd>2.8%</dd>
            </dl>
          </div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};
