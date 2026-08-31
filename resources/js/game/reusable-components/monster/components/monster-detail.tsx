import React, { ReactNode } from 'react';

import MonsterCombatSection from './monster-combat-section';
import MonsterIdentitySection from './monster-identity-section';
import MonsterQuestCelestialSection from './monster-quest-celestial-section';
import MonsterRaidSection from './monster-raid-section';
import MonsterSpellSection from './monster-spell-section';
import MonsterDetailProps from '../types/monster-detail-props';

/**
 * Shared, permission-neutral factual Monster detail presentation. Every
 * value is the Monster's persisted base value; no combat/map scaling is
 * applied. Never checks Admin permission, imports Admin APIs, or mutates
 * data; navigation is entirely driven by the optional `navigation` callbacks.
 */
const MonsterDetail = ({
  monster,
  navigation,
}: MonsterDetailProps): ReactNode => (
  <div className="flex flex-col gap-6">
    <MonsterIdentitySection monster={monster} navigation={navigation} />
    <MonsterCombatSection monster={monster} navigation={navigation} />
    <MonsterSpellSection monster={monster} navigation={navigation} />
    <MonsterQuestCelestialSection monster={monster} navigation={navigation} />
    <MonsterRaidSection monster={monster} navigation={navigation} />
  </div>
);

export default MonsterDetail;
