import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { formatDistanceToNowStrict, parse } from 'date-fns';
import React, { useEffect, useMemo, useState } from 'react';

import {
  FILTER_LABELS,
  FILTER_OPTIONS,
} from './constants/market-history-filter-constants';
import MarketHistoryChartPointDefinition from './definitions/market-history-chart-point-definition';
import MarketHistoryRowDefinition from './definitions/market-history-row-definition';
import MarketHistoryChartTooltip from './partials/item-market-listing/market-history-chart-tooltip';
import ListItemOnMarketProps from './types/list-item-on-market-props';
import { MarketHistoryForTypeFilters } from '../../components/market/api/enums/market-history-for-type-filters';
import { useGetMarketHistoryForType } from '../../components/market/api/hooks/use-get-market-history-for-type';

import { formatNumberWithCommas } from 'game-utils/format-number';

import Button from 'ui/buttons/button';
import DropdownButton from 'ui/buttons/drop-down-button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import LineChart from 'ui/charts/line-chart/line-chart';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MAX_LISTING_PRICE = 2000000000000;

const ListItemOnMarket = ({ type, on_close }: ListItemOnMarketProps) => {
  const { setRequestParams, error, data, loading } =
    useGetMarketHistoryForType();

  const [selectedFilter, setSelectedFilter] =
    useState<MarketHistoryForTypeFilters | null>(null);

  const [listingPrice, setListingPrice] = useState<string>('');
  const [inputError, setInputError] = useState<string | null>(null);

  const dropdownLabel = selectedFilter ? FILTER_LABELS[selectedFilter] : 'All';

  const itemTypeLabel = useMemo(() => {
    return type
      .split('_')
      .map((word) => {
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
      })
      .join(' ');
  }, [type]);

  const chartData = useMemo((): MarketHistoryChartPointDefinition[] => {
    const resolvedRows = (data ?? []) as MarketHistoryRowDefinition[];

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

  const resolvedListingPriceNumber = useMemo(() => {
    const parsedValue = Number(listingPrice);

    if (!Number.isFinite(parsedValue)) {
      return null;
    }

    return parsedValue;
  }, [listingPrice]);

  const isListDisabled = useMemo(() => {
    if (inputError) {
      return true;
    }

    if (resolvedListingPriceNumber === null) {
      return true;
    }

    return resolvedListingPriceNumber <= 0;
  }, [inputError, resolvedListingPriceNumber]);

  const handleLoadMarketHistoryForFilter = (
    nextFilter: MarketHistoryForTypeFilters | null
  ) => {
    setSelectedFilter(nextFilter);

    setRequestParams({
      type: type,
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
    const sanitizedValue = nextValue.replace(/[^\d]/g, '');

    if (sanitizedValue === '') {
      setListingPrice('');
      setInputError(null);
      return;
    }

    const parsedValue = Number(sanitizedValue);

    if (!Number.isFinite(parsedValue)) {
      setListingPrice('');
      setInputError('Please enter a valid number.');
      return;
    }

    if (parsedValue > MAX_LISTING_PRICE) {
      setListingPrice(sanitizedValue);
      setInputError('Max price is 2,000,000,000,000 gold.');
      return;
    }

    setListingPrice(sanitizedValue);
    setInputError(null);
  };

  const handleClickPrimaryButton = () => {
    console.log(listingPrice);
  };

  const handleClickDangerButton = () => {
    on_close();
  };

  const handleClickClearFilterButton = () => {
    console.log('clear filter button clicked');
    handleClearFilters();
  };

  const renderFilterOptionButtons = () => {
    if (FILTER_OPTIONS.length === 0) {
      return null;
    }

    return FILTER_OPTIONS.map((filter) => {
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
    });
  };

  const renderFilterOptions = () => {
    const renderedFilterOptionButtons = renderFilterOptionButtons();

    if (!renderedFilterOptionButtons) {
      return null;
    }

    return <>{renderedFilterOptionButtons}</>;
  };

  const renderChart = () => {
    return (
      <div
        className="text-danube-600 dark:text-danube-300 w-full"
        role="img"
        aria-label="Market history line chart"
      >
        <LineChart
          data={chartData}
          x_axis_data_key="soldWhenTimestamp"
          tooltip_content={<MarketHistoryChartTooltip />}
          responsive_container_props={{ width: '100%', height: 224 }}
          empty_state={
            <div className="py-10 text-center text-sm text-gray-600 italic dark:text-gray-400">
              No market history data available for this period.
            </div>
          }
          outer_container_props={{
            className: 'w-full',
          }}
          chart_props={{
            margin: { top: 8, right: 8, bottom: 0, left: 0 },
          }}
          cartesian_grid_props={{
            stroke: 'currentColor',
            strokeOpacity: 0.15,
          }}
          x_axis_props={{
            type: 'number',
            scale: 'time',
            domain: ['dataMin', 'dataMax'],
            minTickGap: 24,
            tick: { fill: 'currentColor', fontSize: 12 },
            tickFormatter: (value: unknown) => {
              const resolvedValue =
                typeof value === 'number' ? value : Number(value);

              return formatDistanceToNowStrict(new Date(resolvedValue), {
                addSuffix: true,
              });
            },
            axisLine: { stroke: 'currentColor', opacity: 0.35 },
            tickLine: { stroke: 'currentColor', opacity: 0.35 },
          }}
          y_axis_props={{
            width: 56,
            tick: { fill: 'currentColor', fontSize: 12 },
            tickFormatter: (value: unknown) => {
              const resolvedValue =
                typeof value === 'number' ? value : Number(value);

              return formatNumberWithCommas(resolvedValue);
            },
            axisLine: { stroke: 'currentColor', opacity: 0.35 },
            tickLine: { stroke: 'currentColor', opacity: 0.35 },
          }}
          tooltip_props={{
            cursor: { stroke: 'currentColor', strokeOpacity: 0.25 },
          }}
          lines={[
            {
              data_key: 'cost',
              line_props: {
                type: 'monotone',
                stroke: 'currentColor',
                strokeWidth: 2,
                dot: {
                  r: 3,
                  fill: 'currentColor',
                  stroke: 'currentColor',
                },
                activeDot: {
                  r: 5,
                  fill: 'currentColor',
                  stroke: 'currentColor',
                },
              },
            },
          ]}
          footer={
            <p className="mt-2 text-center text-xs text-gray-600 italic dark:text-gray-400">
              This chart represents the last 90 days and how much the item of
              type {itemTypeLabel} has sold for over that period of time
            </p>
          }
        />
      </div>
    );
  };

  const renderListingError = () => {
    if (!inputError) {
      return null;
    }

    return (
      <p className="mt-1 text-xs text-red-600 dark:text-red-400">
        {inputError}
      </p>
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
        <div className="flex items-start justify-between gap-2">
          <h2 className="text-theme-xl text-mango-tango-600 dark:text-mango-tango-300 font-semibold">
            List on the market
          </h2>

          <LinkButton
            label="Close"
            variant={ButtonVariant.DANGER}
            on_click={handleClickDangerButton}
            disabled={false}
            aria_label="clode"
            is_external={false}
            additional_css="whitespace-nowrap"
          />
        </div>

        <p className="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
          List your item on the market to make more gold then if you were to
          sell it to the shop. This is great for unique items, mythical items,
          cosmic items and high end enchanted items as well as alchemy items.
        </p>
      </div>

      <div className="flex w-full items-center justify-end gap-2">
        <LinkButton
          label="Clear Filter"
          variant={ButtonVariant.DANGER}
          on_click={handleClickClearFilterButton}
          disabled={selectedFilter === null}
          aria_label="Clear filter"
          is_external={false}
          additional_css="whitespace-nowrap"
        />

        <DropdownButton label={dropdownLabel} variant={ButtonVariant.PRIMARY}>
          {renderFilterOptions()}
        </DropdownButton>
      </div>

      {renderChart()}

      <div className="mt-1 flex w-full items-start gap-2">
        <div className="w-full">
          <Input
            on_change={handleChangeInput}
            clearable
            place_holder="Enter a price..."
            disabled={false}
            value={listingPrice}
          />
          {renderListingError()}
        </div>

        <Button
          on_click={handleClickPrimaryButton}
          label="List"
          variant={ButtonVariant.PRIMARY}
          additional_css="whitespace-nowrap"
          disabled={isListDisabled}
        />
      </div>
    </div>
  );
};

export default ListItemOnMarket;
