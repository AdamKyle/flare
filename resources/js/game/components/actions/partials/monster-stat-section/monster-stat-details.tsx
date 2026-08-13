import clsx from 'clsx';
import React, { ReactNode } from 'react';

import MonsterAmbushCounterSection from './partials/monster-ambush-counter-section';
import MonsterBasicStatsSection from './partials/monster-basic-stats-section';
import MonsterCoreSection from './partials/monster-core-section';
import MonsterCoreStatsSection from './partials/monster-core-stats-section';
import MonsterDevouringSection from './partials/monster-devouring-section';
import MonsterElementalAtonementSection from './partials/monster-element-atonement-section';
import MonsterRaidSpecialAttackSection from './partials/monster-raid-special-attack-section';
import MonsterResistanceSection from './partials/monster-resistance-section';
import MonsterRewardsSection from './partials/monster-reward-section';
import MonsterSkillSection from './partials/monster-skill-section';
import MonsterStatDetailsProps from './types/monster-stat-details-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const MonsterStatDetails = ({
  monster,
  single_column,
}: MonsterStatDetailsProps): ReactNode => {
  const renderCelestialAlert = () => {
    if (!monster.is_celestial_entity) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        <strong>This creature is a celestial</strong>: Paid conjuration uses
        Gold and Gold Dust. Celestials may also spawn from movement, with an 80%
        movement-spawn chance during the Weekly Celestials event. Use /pc to
        receive the location of an available celestial. Players with the
        required quest item from Hunting Expedition on Surface can use /pct to
        travel to an available celestial. If it survives an attack, it will flee
        to a new location and heal to full health.
      </Alert>
    );
  };

  const renderRaidMonsterAlert = () => {
    if (!monster.is_raid_monster) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        <strong>This creature is a raid monster</strong>: These creatures live
        at specific locations on specific maps while a raid is taking place.
        These creatures can be strong and hard to take down, but they drop quest
        items to progress raid story line quests that lead towards unlocking
        cosmetic based rewards. You can fight them while at the specific
        location(s) and selecting them from the drop down.
      </Alert>
    );
  };

  const renderRaidBossAlert = () => {
    if (!monster.is_raid_boss) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        <strong>This creature is a raid boss</strong>: This beast lives at a
        specific location while a raid is in progress. These creatures cannot be
        taken down alone, and require many players to work together to bring the
        beast down! The player who lands the last hit, gets a full set of gear
        the raid boss raid dropping. Come prepared to die child!
      </Alert>
    );
  };

  return (
    <>
      {renderCelestialAlert()}
      {renderRaidMonsterAlert()}
      {renderRaidBossAlert()}

      <div
        className={clsx(
          'grid grid-cols-1 gap-6',
          !single_column && 'md:grid-cols-2 lg:grid-cols-2'
        )}
      >
        <div>
          <MonsterCoreSection monster={monster} />

          <MonsterRewardsSection monster={monster} />

          <MonsterBasicStatsSection monster={monster} />

          <MonsterRaidSpecialAttackSection monster={monster} />

          <MonsterAmbushCounterSection monster={monster} />

          <MonsterElementalAtonementSection monster={monster} />
        </div>

        <div>
          <MonsterCoreStatsSection monster={monster} />

          <MonsterSkillSection monster={monster} />

          <MonsterResistanceSection monster={monster} />

          <MonsterDevouringSection monster={monster} />
        </div>
      </div>
    </>
  );
};

export default MonsterStatDetails;
