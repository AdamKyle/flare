import React from 'react';

import Section from '../../../../../../reusable-components/viewable-sections/section';
import StatRowPercent from '../../../../../../reusable-components/viewable-sections/stat-row-percent';
import AmbushCounterSectionProps from '../../types/partials/item-view/ambush-and-counter-section-props';

const AmbushCounterSection = ({
  ambushChance,
  ambushResistChance,
  counterChance,
  counterResistChance,
}: AmbushCounterSectionProps) => {
  const allZero =
    ambushChance <= 0 &&
    ambushResistChance <= 0 &&
    counterChance <= 0 &&
    counterResistChance <= 0;

  if (allZero) {
    return null;
  }

  const renderAmbushRow = () => {
    if (ambushChance <= 0) {
      return null;
    }

    return (
      <StatRowPercent
        label="Ambush Chance"
        value={ambushChance}
        tooltip="The chance to ambush the enemy before anyone takes an action."
      />
    );
  };

  const renderAmbushResistRow = () => {
    if (ambushResistChance <= 0) {
      return null;
    }

    return (
      <StatRowPercent
        label="Ambush Resistance"
        value={ambushResistChance}
        tooltip="The chance to resist the enemy’s ambush."
      />
    );
  };

  const renderCounterRow = () => {
    if (counterChance <= 0) {
      return null;
    }

    return (
      <StatRowPercent
        label="Counter Chance"
        value={counterChance}
        tooltip="The chance to counter an enemy’s attack."
      />
    );
  };

  const renderCounterResistRow = () => {
    if (counterResistChance <= 0) {
      return null;
    }

    return (
      <StatRowPercent
        label="Counter Resistance"
        value={counterResistChance}
        tooltip="The chance to avoid the enemy’s counterattack."
      />
    );
  };

  return (
    <Section title="Ambush and Counter">
      {renderAmbushRow()}
      {renderAmbushResistRow()}
      {renderCounterRow()}
      {renderCounterResistRow()}
    </Section>
  );
};

export default AmbushCounterSection;
