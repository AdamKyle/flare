import React, { ReactNode } from 'react';

import DelveProgressSectionProps from './types/delve-progress-section-props';
import ExplorationDetailSection from '../../exploration/components/exploration-detail-section';
import { formatExplorationDuration } from '../../exploration/utils/format-exploration-duration';
import DelveChartPointDefinition from '../types/delve-chart-point-definition';
import { formatDelveStrengthPercentage } from '../utils/format-delve-strength-percentage';

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

const PROGRESS_Y_AXES: LineChartYAxisDefinition[] = [
  {
    key: 'counts',
    type: LineChartYAxisType.COUNT,
    visible: false,
    start_at_zero: true,
    value_formatter: formatNumberWithCommas,
  },
  {
    key: 'strength',
    type: LineChartYAxisType.NUMBER,
    visible: false,
    start_at_zero: true,
    value_formatter: formatDelveStrengthPercentage,
  },
];

const PROGRESS_LINES: Array<
  LineChartLineDefinition<DelveChartPointDefinition>
> = [
  {
    data_key: 'rounds',
    label: 'Rounds',
    color: LineChartColor.DANUBE,
    y_axis_key: 'counts',
    value_formatter: formatNumberWithCommas,
  },
  {
    data_key: 'wins',
    label: 'Wins',
    color: LineChartColor.EMERALD,
    y_axis_key: 'counts',
    value_formatter: formatNumberWithCommas,
  },
  {
    data_key: 'timeouts',
    label: 'Timeouts',
    color: LineChartColor.ROSE,
    y_axis_key: 'counts',
    value_formatter: formatNumberWithCommas,
  },
  {
    data_key: 'enemy_strength_increase',
    label: 'Enemy Strength Increase',
    color: LineChartColor.MARIGOLD,
    y_axis_key: 'strength',
    value_formatter: formatDelveStrengthPercentage,
  },
];

const DelveProgressChart = ({
  chart_points,
}: DelveProgressSectionProps): ReactNode => {
  return (
    <LineChart<DelveChartPointDefinition>
      data={chart_points}
      x_data_key="elapsed_seconds"
      x_label="Elapsed Time"
      x_axis_type={LineChartXAxisType.NUMBER}
      x_formatter={formatExplorationDuration}
      y_axes={PROGRESS_Y_AXES}
      lines={PROGRESS_LINES}
      accessibility_label="Delve progress line chart showing cumulative rounds, wins, timeouts, and the enemy strength increase over elapsed Delve time."
      empty_state={
        <p className="text-sm text-gray-600 italic dark:text-gray-400">
          Delve progress will appear once the first round completes.
        </p>
      }
    />
  );
};

const DelveProgressBreakDown = ({
  totals,
}: DelveProgressSectionProps): ReactNode => {
  return (
    <Dl>
      <Dt>Rounds</Dt>
      <Dd>{formatNumberWithCommas(totals.rounds)}</Dd>
      <Dt>Wins</Dt>
      <Dd>{formatNumberWithCommas(totals.wins)}</Dd>
      <Dt>Timeouts</Dt>
      <Dd>{formatNumberWithCommas(totals.timeouts)}</Dd>
      <Dt>Configured Pack Size</Dt>
      <Dd>{formatNumberWithCommas(totals.pack_size)}</Dd>
      <Dt>Enemy Strength Increase</Dt>
      <Dd>{formatDelveStrengthPercentage(totals.enemy_strength_increase)}</Dd>
    </Dl>
  );
};

const DelveProgressSection = (props: DelveProgressSectionProps): ReactNode => {
  const tabs = [
    {
      label: 'Delve Progress Chart',
      component: DelveProgressChart,
      props,
    },
    {
      label: 'Delve Progress Break Down',
      component: DelveProgressBreakDown,
      props,
    },
  ] as const;

  return (
    <ExplorationDetailSection title="Delve Progress">
      <PillTabs
        tabs={tabs}
        ariaLabel="Delve Progress views"
        additional_tab_css="w-full"
        initialIndex={0}
      />
    </ExplorationDetailSection>
  );
};

export default DelveProgressSection;
