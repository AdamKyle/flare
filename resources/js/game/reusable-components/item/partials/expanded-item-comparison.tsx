import React from 'react';

import ExpandedItemComparisonProps from '../types/partials/expanded-item-comparison-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

interface Section {
  title: string;
  fields: [string, number][];
}

const ExpandedItemComparison = ({
  expandedDetails,
}: ExpandedItemComparisonProps) => {
  const sections: Section[] = [
    {
      title: 'Counter & Ambush',
      fields: [
        ['Counter Chance', expandedDetails.counter_chance_adjustment],
        ['Counter Resistance', expandedDetails.counter_resistance_adjustment],
        ['Ambush Chance', expandedDetails.ambush_chance_adjustment],
        ['Ambush Resistance', expandedDetails.ambush_resistance_adjustment],
      ],
    },
    {
      title: 'Enemy Stat Reductions',
      fields: [
        ['Strength', expandedDetails.str_reduction],
        ['Durability', expandedDetails.dur_reduction],
        ['Dexterity', expandedDetails.dex_reduction],
        ['Intelligence', expandedDetails.int_reduction],
        ['Charisma', expandedDetails.chr_reduction],
        ['Agility', expandedDetails.agi_reduction],
        ['Focus', expandedDetails.focus_reduction],
      ],
    },
    {
      title: 'Skill Adjustments',
      fields: expandedDetails.skills.map((s): [string, number] => [
        s.skill_name,
        s.skill_bonus,
      ]),
    },
  ];

  const renderField = (label: string, value: number, isPercent = true) => {
    if (value === 0) {
      return null;
    }

    const absVal = Math.abs(value);
    const display = isPercent ? `${(absVal * 100).toFixed(2)}%` : `${absVal}`;
    const color =
      value > 0
        ? 'text-emerald-500 dark:text-emerald-300'
        : 'text-rose-500 dark:text-rose-300';
    const prefix = value > 0 ? '+' : '-';
    return (
      <React.Fragment key={label}>
        <Dt>{label}</Dt>
        <Dd>
          <span className={color}>{`${prefix}${display}`}</span>
        </Dd>
      </React.Fragment>
    );
  };

  return (
    <>
      {sections.map(({ title, fields }) => {
        const items = fields.filter(([, val]) => val !== 0);
        if (items.length === 0) return null;
        return (
          <React.Fragment key={title}>
            <Separator />
            <h4 className="text-sm font-medium text-danube-600 dark:text-danube-300 mb-2">
              {title}
            </h4>
            <Separator />
            <Dl>{items.map(([label, val]) => renderField(label, val))}</Dl>
          </React.Fragment>
        );
      })}
    </>
  );
};

export default ExpandedItemComparison;
