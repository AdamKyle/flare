import React, { ReactNode } from 'react';

import DelveCombatSummarySectionProps from './types/delve-combat-summary-section-props';
import ExplorationDetailSection from '../../exploration/components/exploration-detail-section';
import { formatExplorationDuration } from '../../exploration/utils/format-exploration-duration';
import DelveChartPointDefinition from '../types/delve-chart-point-definition';

import { formatNumberWithCommas } from 'game-utils/format-number';

import LineChartLineDefinition from 'ui/charts/line-chart/definitions/line-chart-line-definition';
import LineChartYAxisDefinition from 'ui/charts/line-chart/definitions/line-chart-y-axis-definition';
import LineChartColor from 'ui/charts/line-chart/enums/line-chart-color';
import LineChartXAxisType from 'ui/charts/line-chart/enums/line-chart-x-axis-type';
import LineChartYAxisType from 'ui/charts/line-chart/enums/line-chart-y-axis-type';
import LineChart from 'ui/charts/line-chart/line-chart';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import PillTabs from 'ui/tabs/pill-tabs';

const COMBAT_Y_AXES: LineChartYAxisDefinition[] = [
  {
    key: 'combat',
    type: LineChartYAxisType.NUMBER,
    visible: false,
    start_at_zero: true,
    value_formatter: formatNumberWithCommas,
  },
];

const COMBAT_LINES: Array<LineChartLineDefinition<DelveChartPointDefinition>> =
  [
    {
      data_key: 'weapon_damage',
      label: 'Weapon Damage',
      color: LineChartColor.ROSE,
      y_axis_key: 'combat',
      value_formatter: formatNumberWithCommas,
    },
    {
      data_key: 'spell_damage',
      label: 'Spell Damage',
      color: LineChartColor.REGENT_ST_BLUE,
      y_axis_key: 'combat',
      value_formatter: formatNumberWithCommas,
    },
    {
      data_key: 'healing',
      label: 'Healing',
      color: LineChartColor.EMERALD,
      y_axis_key: 'combat',
      value_formatter: formatNumberWithCommas,
    },
    {
      data_key: 'blocked',
      label: 'Blocked Damage',
      color: LineChartColor.MARIGOLD,
      y_axis_key: 'combat',
      value_formatter: formatNumberWithCommas,
    },
  ];

const DelveCombatSummaryChart = ({
  chart_points,
}: DelveCombatSummarySectionProps): ReactNode => {
  return (
    <LineChart<DelveChartPointDefinition>
      data={chart_points}
      x_data_key="elapsed_seconds"
      x_label="Elapsed Time"
      x_axis_type={LineChartXAxisType.NUMBER}
      x_formatter={formatExplorationDuration}
      y_axes={COMBAT_Y_AXES}
      lines={COMBAT_LINES}
      accessibility_label="Delve combat summary line chart showing cumulative weapon damage, spell damage, healing, and blocked damage over elapsed Delve time."
      empty_state={
        <p className="text-sm text-gray-600 italic dark:text-gray-400">
          Combat totals will appear once the first round completes.
        </p>
      }
    />
  );
};

const DelveCombatSummaryBreakDown = ({
  damage,
  healing,
  blocked,
}: DelveCombatSummarySectionProps): ReactNode => {
  return (
    <Dl>
      <Dt>Weapon Damage</Dt>
      <Dd>{formatNumberWithCommas(damage.weapon)}</Dd>
      <Dt>Spell Damage</Dt>
      <Dd>{formatNumberWithCommas(damage.spell)}</Dd>
      <Dt>Healing</Dt>
      <Dd>{formatNumberWithCommas(healing)}</Dd>
      <Dt>Blocked Damage</Dt>
      <Dd>{formatNumberWithCommas(blocked)}</Dd>
    </Dl>
  );
};

const DelveCombatSummarySection = (
  props: DelveCombatSummarySectionProps
): ReactNode => {
  const tabs = [
    {
      label: 'Combat Summary Chart',
      component: DelveCombatSummaryChart,
      props,
    },
    {
      label: 'Combat Summary Break Down',
      component: DelveCombatSummaryBreakDown,
      props,
    },
  ] as const;

  return (
    <ExplorationDetailSection title="Combat Summary">
      <PillTabs
        tabs={tabs}
        ariaLabel="Delve Combat Summary views"
        additional_tab_css="w-full"
        initialIndex={0}
      />
    </ExplorationDetailSection>
  );
};

export default DelveCombatSummarySection;
