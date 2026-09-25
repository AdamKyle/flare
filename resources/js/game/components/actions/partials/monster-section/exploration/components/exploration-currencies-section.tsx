import React, { ReactNode, useMemo } from 'react';

import ExplorationDetailSection from './exploration-detail-section';
import CurrencyDisplay from '../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../reusable-components/currency/enums/currency-type';
import { formatCurrencyText } from '../../../../../../reusable-components/currency/utils/format-currency-text';
import ExplorationChartPointDefinition from '../types/exploration-chart-point-definition';
import ExplorationCurrenciesSectionProps from '../types/exploration-currencies-section-props';
import { formatExplorationDuration } from '../utils/format-exploration-duration';
import {
  isDisplayableExplorationCurrency,
  resolveExplorationCurrencyLabel,
  resolveExplorationCurrencyType,
} from '../utils/resolve-exploration-currency-label';

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

const CURRENCY_Y_AXES: LineChartYAxisDefinition[] = [
  {
    key: 'currencies',
    type: LineChartYAxisType.NUMBER,
    visible: false,
    start_at_zero: true,
    value_formatter: formatNumberWithCommas,
  },
];

const CURRENCY_LINES: Array<
  LineChartLineDefinition<ExplorationChartPointDefinition>
> = [
  {
    data_key: 'gold',
    label: 'Gold',
    color: LineChartColor.MARIGOLD,
    y_axis_key: 'currencies',
    value_formatter: (value) => formatCurrencyText(CurrencyType.GOLD, value),
    value_renderer: (value) => (
      <CurrencyDisplay
        currency={CurrencyType.GOLD}
        amount={value}
        display_mode={CurrencyDisplayMode.EXACT}
      />
    ),
  },
  {
    data_key: 'gold_dust',
    label: 'Gold Dust',
    color: LineChartColor.REGENT_ST_BLUE,
    y_axis_key: 'currencies',
    value_formatter: (value) =>
      formatCurrencyText(CurrencyType.GOLD_DUST, value),
    value_renderer: (value) => (
      <CurrencyDisplay
        currency={CurrencyType.GOLD_DUST}
        amount={value}
        display_mode={CurrencyDisplayMode.EXACT}
      />
    ),
  },
  {
    data_key: 'shards',
    label: 'Shards',
    color: LineChartColor.DANUBE,
    y_axis_key: 'currencies',
    value_formatter: (value) => formatCurrencyText(CurrencyType.SHARDS, value),
    value_renderer: (value) => (
      <CurrencyDisplay
        currency={CurrencyType.SHARDS}
        amount={value}
        display_mode={CurrencyDisplayMode.EXACT}
      />
    ),
  },
  {
    data_key: 'copper_coins',
    label: 'Copper Coins',
    color: LineChartColor.ROSE,
    y_axis_key: 'currencies',
    value_formatter: (value) =>
      formatCurrencyText(CurrencyType.COPPER_COINS, value),
    value_renderer: (value) => (
      <CurrencyDisplay
        currency={CurrencyType.COPPER_COINS}
        amount={value}
        display_mode={CurrencyDisplayMode.EXACT}
      />
    ),
  },
  {
    data_key: 'levels_gained',
    label: 'Levels Gained',
    color: LineChartColor.EMERALD,
    y_axis_key: 'currencies',
    value_formatter: formatNumberWithCommas,
  },
];

const resolveCurrencyEntries = (
  currencies: Record<string, number>
): Array<[string, number]> =>
  Object.entries(currencies).filter(
    ([currency, amount]) =>
      isDisplayableExplorationCurrency(currency) && amount !== 0
  );

const buildAccessibilityLabel = (
  lines: Array<LineChartLineDefinition<ExplorationChartPointDefinition>>
): string =>
  `Currencies gained line chart showing cumulative ${lines.map((line) => line.label).join(', ')} over elapsed Exploration time.`;

const ExplorationCurrenciesChart = ({
  chart_points,
}: ExplorationCurrenciesSectionProps): ReactNode => {
  const activeLines = useMemo(
    () =>
      CURRENCY_LINES.filter((line) =>
        chart_points.some((point) => point[line.data_key] !== 0)
      ),
    [chart_points]
  );

  if (activeLines.length === 0) {
    return (
      <p className="text-sm text-gray-600 italic dark:text-gray-400">
        Currency gains will appear on the chart once an encounter rewards them.
      </p>
    );
  }

  return (
    <LineChart<ExplorationChartPointDefinition>
      data={chart_points}
      x_data_key="elapsed_seconds"
      x_label="Elapsed Time"
      x_axis_type={LineChartXAxisType.NUMBER}
      x_formatter={formatExplorationDuration}
      y_axes={CURRENCY_Y_AXES}
      lines={activeLines}
      accessibility_label={buildAccessibilityLabel(activeLines)}
    />
  );
};

const ExplorationCurrenciesBreakDown = ({
  currencies,
}: ExplorationCurrenciesSectionProps): ReactNode => {
  const renderAmount = (currency: string, amount: number): ReactNode => {
    const currencyType = resolveExplorationCurrencyType(currency);

    if (!currencyType) {
      return formatIntWithPlus(amount);
    }

    return (
      <CurrencyDisplay
        currency={currencyType}
        amount={amount}
        display_mode={CurrencyDisplayMode.EXACT}
        show_label={false}
      />
    );
  };

  const renderCurrency = ([currency, amount]: [string, number]) => (
    <React.Fragment key={currency}>
      <Dt>{resolveExplorationCurrencyLabel(currency)}</Dt>
      <Dd>{renderAmount(currency, amount)}</Dd>
    </React.Fragment>
  );

  return <Dl>{resolveCurrencyEntries(currencies).map(renderCurrency)}</Dl>;
};

const ExplorationCurrenciesSection = (
  props: ExplorationCurrenciesSectionProps
): ReactNode => {
  const hasCurrencyEntries =
    resolveCurrencyEntries(props.currencies).length > 0;

  if (!hasCurrencyEntries) {
    return null;
  }

  const tabs = [
    {
      label: 'Currencies Gained Chart',
      component: ExplorationCurrenciesChart,
      props,
    },
    {
      label: 'Currencies Gained Break Down',
      component: ExplorationCurrenciesBreakDown,
      props,
    },
  ] as const;

  return (
    <ExplorationDetailSection title="Currencies Gained">
      <PillTabs
        tabs={tabs}
        ariaLabel="Currencies Gained views"
        additional_tab_css="w-full"
        initialIndex={0}
      />
    </ExplorationDetailSection>
  );
};

export default ExplorationCurrenciesSection;
