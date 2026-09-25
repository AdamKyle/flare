import React, { ReactNode } from 'react';

import ExplorationDetailSection from './exploration-detail-section';
import ExplorationChartPointDefinition from '../types/exploration-chart-point-definition';
import ExplorationProgressSectionProps from '../types/exploration-progress-section-props';
import { formatExplorationDuration } from '../utils/format-exploration-duration';

import {
  formatIntWithPlus,
  formatNumberWithCommas,
} from 'game-utils/format-number';

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
    key: 'rewards',
    type: LineChartYAxisType.NUMBER,
    visible: false,
    start_at_zero: true,
    value_formatter: formatNumberWithCommas,
  },
];

const PROGRESS_LINES: Array<
  LineChartLineDefinition<ExplorationChartPointDefinition>
> = [
  {
    data_key: 'fights',
    label: 'Fights',
    color: LineChartColor.DANUBE,
    y_axis_key: 'counts',
    value_formatter: formatNumberWithCommas,
  },
  {
    data_key: 'kills',
    label: 'Kills',
    color: LineChartColor.ROSE,
    y_axis_key: 'counts',
    value_formatter: formatNumberWithCommas,
  },
  {
    data_key: 'xp',
    label: 'XP Gained',
    color: LineChartColor.EMERALD,
    y_axis_key: 'rewards',
    value_formatter: formatNumberWithCommas,
  },
  {
    data_key: 'skill_xp',
    label: 'Skill XP Gained',
    color: LineChartColor.REGENT_ST_BLUE,
    y_axis_key: 'rewards',
    value_formatter: formatNumberWithCommas,
  },
  {
    data_key: 'faction_points',
    label: 'Faction Points Gained',
    color: LineChartColor.MARIGOLD,
    y_axis_key: 'rewards',
    value_formatter: formatNumberWithCommas,
  },
];

const ExplorationProgressChart = ({
  chart_points,
}: ExplorationProgressSectionProps): ReactNode => {
  return (
    <LineChart<ExplorationChartPointDefinition>
      data={chart_points}
      x_data_key="elapsed_seconds"
      x_label="Elapsed Time"
      x_axis_type={LineChartXAxisType.NUMBER}
      x_formatter={formatExplorationDuration}
      y_axes={PROGRESS_Y_AXES}
      lines={PROGRESS_LINES}
      accessibility_label="Exploration progress line chart showing cumulative fights, kills, XP gained, skill XP gained, and faction points gained over elapsed Exploration time."
      empty_state={
        <p className="text-sm text-gray-600 italic dark:text-gray-400">
          Exploration progress will appear once the first encounter completes.
        </p>
      }
    />
  );
};

const ExplorationProgressBreakDown = ({
  totals,
}: ExplorationProgressSectionProps): ReactNode => {
  return (
    <Dl>
      <Dt>Fights</Dt>
      <Dd>{formatNumberWithCommas(totals.fights)}</Dd>
      <Dt>Kills</Dt>
      <Dd>{formatNumberWithCommas(totals.kills)}</Dd>
      <Dt>XP Gained</Dt>
      <Dd>{formatIntWithPlus(totals.xp)}</Dd>
      <Dt>Skill XP Gained</Dt>
      <Dd>{formatIntWithPlus(totals.skill_xp)}</Dd>
      <Dt>Faction Points Gained</Dt>
      <Dd>{formatIntWithPlus(totals.faction_points)}</Dd>
    </Dl>
  );
};

const ExplorationProgressSection = (
  props: ExplorationProgressSectionProps
): ReactNode => {
  const tabs = [
    {
      label: 'Exploration Progress Chart',
      component: ExplorationProgressChart,
      props,
    },
    {
      label: 'Exploration Progress Break Down',
      component: ExplorationProgressBreakDown,
      props,
    },
  ] as const;

  return (
    <ExplorationDetailSection title="Exploration Progress">
      <PillTabs
        tabs={tabs}
        ariaLabel="Exploration Progress views"
        additional_tab_css="w-full"
        initialIndex={0}
      />
    </ExplorationDetailSection>
  );
};

export default ExplorationProgressSection;
