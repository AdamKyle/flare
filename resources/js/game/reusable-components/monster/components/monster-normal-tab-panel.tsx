import React, { Fragment, ReactNode } from 'react';

import MonsterAccuracyEvasionSection from './monster-accuracy-evasion-section';
import MonsterAmbushCounterSection from './monster-ambush-counter-section';
import MonsterCombatSection from './monster-combat-section';
import MonsterDamageHealingSection from './monster-damage-healing-section';
import MonsterDevouringSection from './monster-devouring-section';
import MonsterIdentitySection from './monster-identity-section';
import MonsterQuestCelestialSection from './monster-quest-celestial-section';
import MonsterRaidSection from './monster-raid-section';
import MonsterResistancesSection from './monster-resistances-section';
import MonsterRewardsSection from './monster-rewards-section';
import MonsterSpellSection from './monster-spell-section';
import MonsterNormalTabPanelProps from '../types/monster-normal-tab-panel-props';

import DetailGridRow from 'ui/detail-grid/detail-grid-row';
import Separator from 'ui/separator/separator';

const MonsterNormalTabPanel = ({
  monster,
  navigation,
}: MonsterNormalTabPanelProps): ReactNode => {
  const rowGroups: ReactNode[][] = [
    [
      MonsterIdentitySection({ monster, navigation }),
      MonsterRewardsSection({ monster }),
    ],
    [
      MonsterCombatSection({ monster }),
      MonsterDamageHealingSection({ monster }),
    ],
    [
      MonsterAccuracyEvasionSection({ monster }),
      MonsterResistancesSection({ monster }),
    ],
    [
      MonsterAmbushCounterSection({ monster }),
      MonsterDevouringSection({ monster }),
    ],
    [
      MonsterSpellSection({ monster }),
      MonsterQuestCelestialSection({ monster, navigation }),
    ],
    [MonsterRaidSection({ monster, navigation })],
  ];

  const visibleRows = rowGroups
    .map((sections) => sections.filter((section) => section !== null))
    .filter((sections) => sections.length > 0);

  return (
    <div className="flex flex-col">
      {visibleRows.map((sections, index) => (
        <Fragment key={index}>
          {index > 0 && <Separator additional_css="my-1" />}
          <DetailGridRow>
            {sections.map((section, sectionIndex) => (
              <Fragment key={sectionIndex}>{section}</Fragment>
            ))}
          </DetailGridRow>
        </Fragment>
      ))}
    </div>
  );
};

export default MonsterNormalTabPanel;
