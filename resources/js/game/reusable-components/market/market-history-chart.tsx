import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { formatDistanceToNowStrict } from 'date-fns';
import React, { ReactNode, useMemo, useState } from 'react';

import MarketHistoryChartProps from './types/market-history-chart-props';
import { buildMarketHistoryChartPoints } from './utils/build-market-history-chart-points';
import { MarketHistoryForTypeFilters } from '../../components/market/api/enums/market-history-for-type-filters';
import { useGetMarketHistoryForType } from '../../components/market/api/hooks/use-get-market-history-for-type';
import CurrencyDisplay from '../currency/currency-display';
import { CurrencyDisplayMode } from '../currency/enums/currency-display-mode';
import { CurrencyType } from '../currency/enums/currency-type';
import { formatCurrencyText } from '../currency/utils/format-currency-text';
import {
  FILTER_LABELS,
  FILTER_OPTIONS,
} from '../item/constants/market-history-filter-constants';
import MarketHistoryChartPointDefinition from '../item/definitions/market-history-chart-point-definition';

import { formatNumberWithCommas } from 'game-utils/format-number';

import DropDownButton from 'ui/buttons/drop-down-button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import LineChartColor from 'ui/charts/line-chart/enums/line-chart-color';
import LineChartXAxisType from 'ui/charts/line-chart/enums/line-chart-x-axis-type';
import LineChartYAxisType from 'ui/charts/line-chart/enums/line-chart-y-axis-type';
import LineChart from 'ui/charts/line-chart/line-chart';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const formatGoldText = (value: number): string =>
  formatCurrencyText(CurrencyType.GOLD, value);

const renderGoldValue = (value: number): ReactNode => (
  <CurrencyDisplay
    currency={CurrencyType.GOLD}
    amount={value}
    display_mode={CurrencyDisplayMode.EXACT}
  />
);

const formatSaleTime = (value: number): string =>
  formatDistanceToNowStrict(new Date(value), { addSuffix: true });

const MarketHistoryChart = ({ item_type }: MarketHistoryChartProps) => {
  const [selectedFilter, setSelectedFilter] =
    useState<MarketHistoryForTypeFilters | null>(null);

  const { data, loading, error } = useGetMarketHistoryForType({
    type: item_type,
    filter: selectedFilter,
  });

  const chartData = useMemo(() => buildMarketHistoryChartPoints(data), [data]);

  const filterDropDownData = {
    dropdown_label: selectedFilter ? FILTER_LABELS[selectedFilter] : 'All',
    items: FILTER_OPTIONS.map((filter) => ({
      label: FILTER_LABELS[filter],
      value: filter,
      aria_label: FILTER_LABELS[filter],
    })),
  };

  const handleApplyFilter = (filter: MarketHistoryForTypeFilters) => {
    setSelectedFilter(filter);
  };

  const handleClearFilter = () => {
    setSelectedFilter(null);
  };

  const renderChart = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    return (
      <LineChart<MarketHistoryChartPointDefinition>
        data={chartData}
        x_data_key="soldWhenTimestamp"
        x_label="Sale Time"
        x_axis_type={LineChartXAxisType.TIME}
        x_formatter={formatSaleTime}
        y_axes={[
          {
            key: 'price',
            type: LineChartYAxisType.NUMBER,
            visible: true,
            start_at_zero: false,
            value_formatter: formatNumberWithCommas,
          },
        ]}
        lines={[
          {
            data_key: 'cost',
            label: 'Gold Sale Price',
            color: LineChartColor.DANUBE,
            y_axis_key: 'price',
            value_formatter: formatGoldText,
            value_renderer: renderGoldValue,
            show_points: true,
          },
        ]}
        accessibility_label="Market history line chart showing Gold sale prices over time."
        show_legend={false}
        empty_state={
          <div className="py-10 text-center text-sm text-gray-600 italic dark:text-gray-400">
            No market history data available for this period.
          </div>
        }
        footer={
          <p className="mt-2 text-center text-xs text-gray-600 italic dark:text-gray-400">
            This chart shows what items of the same type sold for over the last
            90 days.
          </p>
        }
      />
    );
  };

  return (
    <div className="flex flex-col gap-3">
      <div className="flex w-full items-center justify-end gap-2">
        <LinkButton
          label="Clear Filter"
          variant={ButtonVariant.DANGER}
          on_click={handleClearFilter}
          disabled={selectedFilter === null}
          aria_label="Clear filter"
          additional_css="whitespace-nowrap"
        />

        <DropDownButton
          data={filterDropDownData}
          on_select={handleApplyFilter}
        />
      </div>

      {renderChart()}
    </div>
  );
};

export default MarketHistoryChart;
