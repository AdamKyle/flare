import React, { useEffect, useMemo, useState } from 'react';
import { formatDistanceToNowStrict, parse } from 'date-fns';
import {
  CartesianGrid,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  useActiveTooltipDataPoints,
  useActiveTooltipLabel,
  XAxis,
  YAxis,
} from 'recharts';

import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { formatNumberWithCommas } from 'game-utils/format-number';
import Button from 'ui/buttons/button';
import DropdownButton from 'ui/buttons/drop-down-button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Input from 'ui/input/input';

import { useGetMarketHistoryForType } from '../../components/market/api/hooks/use-get-market-history-for-type';
import ListItemOnMarketProps from './types/list-item-on-market-props';
import {
  InventoryItemTypes
} from "../../components/character-sheet/partials/character-inventory/enums/inventory-item-types";

export enum MarketHistoryForTypeFilters {
  SINGLE_ENCHANT = 'single_enchant',
  DOUBLE_ENCHANT = 'double_enchant',
  UNIQUE = 'unique',
  MYTHIC = 'mythic',
  COSMIC = 'cosmic',
}

interface MarketHistoryRowType {
  cost: number;
  affix_name: string;
  sold_when: string;
}

interface MarketHistoryChartPointType {
  soldWhenTimestamp: number;
  cost: number;
  affixName: string;
}

const FILTER_LABELS: Record<MarketHistoryForTypeFilters, string> = {
  [MarketHistoryForTypeFilters.SINGLE_ENCHANT]: 'Single Enchant',
  [MarketHistoryForTypeFilters.DOUBLE_ENCHANT]: 'Double Enchant',
  [MarketHistoryForTypeFilters.UNIQUE]: 'Unique',
  [MarketHistoryForTypeFilters.MYTHIC]: 'Mythic',
  [MarketHistoryForTypeFilters.COSMIC]: 'Cosmic',
};

const FILTER_OPTIONS: MarketHistoryForTypeFilters[] = [
  MarketHistoryForTypeFilters.SINGLE_ENCHANT,
  MarketHistoryForTypeFilters.DOUBLE_ENCHANT,
  MarketHistoryForTypeFilters.UNIQUE,
  MarketHistoryForTypeFilters.MYTHIC,
  MarketHistoryForTypeFilters.COSMIC,
];

const MarketHistoryChartTooltip = () => {
  const activeDataPoints =
    useActiveTooltipDataPoints<MarketHistoryChartPointType>();
  const activeLabel = useActiveTooltipLabel();

  if (!activeDataPoints?.length) {
    return null;
  }

  const firstPoint = activeDataPoints[0];

  if (
    !firstPoint ||
    typeof firstPoint.cost !== 'number' ||
    typeof firstPoint.soldWhenTimestamp !== 'number'
  ) {
    return null;
  }

  const resolvedTimestamp =
    typeof activeLabel === 'number' ? activeLabel : firstPoint.soldWhenTimestamp;

  return (
    <div
      role="tooltip"
      className="rounded-sm bg-gray-100/90 p-2 text-xs text-gray-900 dark:bg-gray-800/90 dark:text-gray-100"
    >
      <div className="font-medium">
        {formatNumberWithCommas(firstPoint.cost)} gold
      </div>
      <div className="mt-0.5">
        {formatDistanceToNowStrict(new Date(resolvedTimestamp), {
          addSuffix: true,
        })}
      </div>
    </div>
  );
};

const ListItemOnMarket = ({ type }: ListItemOnMarketProps) => {
  const { setRequestParams, error, data, loading } =
    useGetMarketHistoryForType();

  const [selectedFilter, setSelectedFilter] =
    useState<MarketHistoryForTypeFilters | null>(null);

  const dropdownLabel = selectedFilter ? FILTER_LABELS[selectedFilter] : 'All';

  const itemTypeLabel = useMemo(() => {
    if (typeof type !== 'string') {
      return StringInventoryItemTypes(type);
    }

    return type
      .split('_')
      .map((word) => {
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
      })
      .join(' ');
  }, [type]);

  const chartData = useMemo((): MarketHistoryChartPointType[] => {
    const resolvedRows = (data ?? []) as MarketHistoryRowType[];

    return resolvedRows
      .map((row) => {
        const soldWhen = parse(
          row.sold_when,
          'yyyy-MM-dd HH:mm:ss',
          new Date()
        );

        return {
          soldWhenTimestamp: soldWhen.getTime(),
          cost: row.cost,
          affixName: row.affix_name,
        };
      })
      .sort((firstPoint, secondPoint) => {
        return firstPoint.soldWhenTimestamp - secondPoint.soldWhenTimestamp;
      });
  }, [data]);

  const handleLoadMarketHistoryForFilter = (
    nextFilter: MarketHistoryForTypeFilters | null
  ) => {
    setSelectedFilter(nextFilter);

    setRequestParams({
      type: InventoryItemTypes.SPELL_HEALING,
      filter: nextFilter,
    });
  };

  useEffect(() => {
    handleLoadMarketHistoryForFilter(null);
  }, [type]);

  const handleApplyFilter = (filter: MarketHistoryForTypeFilters) => {
    handleLoadMarketHistoryForFilter(filter);
  };

  const handleClearFilters = () => {
    handleLoadMarketHistoryForFilter(null);
  };

  const handleChangeInput = (nextValue: string) => {
    console.log(nextValue);
  };

  const handleClickPrimaryButton = () => {};

  const renderFilterOptions = () => {
    if (FILTER_OPTIONS.length === 0) {
      return null;
    }

    return (
      <>
        <button
          type="button"
          role="menuitem"
          className="rounded-sm px-2 py-1 text-left hover:bg-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:hover:bg-gray-500"
          onClick={handleClearFilters}
        >
          All
        </button>

        {FILTER_OPTIONS.map((filter) => {
          return (
            <button
              key={filter}
              type="button"
              role="menuitem"
              className="rounded-sm px-2 py-1 text-left hover:bg-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:hover:bg-gray-500"
              onClick={() => {
                handleApplyFilter(filter);
              }}
            >
              {FILTER_LABELS[filter]}
            </button>
          );
        })}
      </>
    );
  };

  const renderChart = () => {
    if (chartData.length === 0) {
      return (
        <div className="py-10 text-center text-sm text-gray-600 italic dark:text-gray-400">
          No market history data available for this period.
        </div>
      );
    }

    return (
      <div
        className="text-danube-600 dark:text-danube-300 w-full"
        role="img"
        aria-label="Market history line chart"
      >
        <div className="h-56 w-full">
          <ResponsiveContainer width="100%" height="100%">
            <LineChart
              data={chartData}
              margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
            >
              <CartesianGrid stroke="currentColor" strokeOpacity={0.15} />
              <XAxis
                dataKey="soldWhenTimestamp"
                type="number"
                scale="time"
                domain={['dataMin', 'dataMax']}
                minTickGap={24}
                tick={{ fill: 'currentColor', fontSize: 12 }}
                tickFormatter={(value) => {
                  return formatDistanceToNowStrict(new Date(Number(value)), {
                    addSuffix: true,
                  });
                }}
                axisLine={{ stroke: 'currentColor', opacity: 0.35 }}
                tickLine={{ stroke: 'currentColor', opacity: 0.35 }}
              />
              <YAxis
                width={56}
                tick={{ fill: 'currentColor', fontSize: 12 }}
                tickFormatter={(value) => {
                  return formatNumberWithCommas(Number(value));
                }}
                axisLine={{ stroke: 'currentColor', opacity: 0.35 }}
                tickLine={{ stroke: 'currentColor', opacity: 0.35 }}
              />
              <Tooltip
                content={<MarketHistoryChartTooltip />}
                cursor={{ stroke: 'currentColor', strokeOpacity: 0.25 }}
              />
              <Line
                type="monotone"
                dataKey="cost"
                stroke="currentColor"
                strokeWidth={2}
                dot={{ r: 3, fill: 'currentColor', stroke: 'currentColor' }}
                activeDot={{
                  r: 5,
                  fill: 'currentColor',
                  stroke: 'currentColor',
                }}
              />
            </LineChart>
          </ResponsiveContainer>
        </div>

        <p className="mt-2 text-center text-xs text-gray-600 italic dark:text-gray-400">
          This chart repersents the last 90 days and how much the item of type{' '}
          {itemTypeLabel} has sold for over that period of time
        </p>
      </div>
    );
  };

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  return (
    <div className="container flex flex-col gap-3">
      <div className="flex flex-col gap-1">
        <h2 className="text-theme-xl text-mango-tango-600 dark:text-mango-tango-300 font-semibold">
          List on the market
        </h2>
        <p className="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
          List your item on the market to make more gold then if you were to
          sell it to the shop. This is great for unique items, mythical items,
          cosmic items and high end enchanted items as well as alchemy items.
        </p>
      </div>

      <div className="flex w-full justify-end">
        <DropdownButton label={dropdownLabel} variant={ButtonVariant.PRIMARY}>
          {renderFilterOptions()}
        </DropdownButton>
      </div>

      {renderChart()}

      <div className="mt-1 flex w-full items-stretch gap-2">
        <Input
          on_change={handleChangeInput}
          clearable
          place_holder="Enter a price..."
          disabled={false}
        />
        <Button
          on_click={handleClickPrimaryButton}
          label="List"
          variant={ButtonVariant.PRIMARY}
          additional_css="whitespace-nowrap"
        />
      </div>
    </div>
  );
};

export default ListItemOnMarket;
