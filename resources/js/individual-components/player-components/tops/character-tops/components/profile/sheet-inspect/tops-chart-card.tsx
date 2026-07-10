import React from "react";
import { AxisOptions, Chart } from "react-charts";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import { formatNumber } from "../../../../../../../game/lib/game/format-number";

type ChartPoint = {
    label: string;
    value: number;
    date?: string;
};

type ChartSeries = {
    label: string;
    points: ChartPoint[];
};

type ChartPayload = {
    source?: string;
    unit?: string;
    granularity?: string | null;
    points?: ChartPoint[];
    series?: ChartSeries[];
};

type ReactChartPoint = {
    label: string;
    value: number;
};

type ReactChartSeries = {
    label: string;
    data: ReactChartPoint[];
};

function chartId(title: string): string {
    return title.toLowerCase().replace(/[^a-z0-9]+/g, "-");
}

function pointTime(point: ChartPoint): number | null {
    const source = point.date ?? point.label;
    const time = new Date(source).getTime();

    return Number.isNaN(time) ? null : time;
}

function dateFilterValue(value: string): number | null {
    if (value === "") {
        return null;
    }

    const time = new Date(value + "T00:00:00").getTime();

    return Number.isNaN(time) ? null : time;
}

function filteredPoints(
    points: ChartPoint[],
    startDate: string,
    endDate: string,
): ChartPoint[] {
    const start = dateFilterValue(startDate);
    const end = dateFilterValue(endDate);

    if (start === null && end === null) {
        return points;
    }

    return points.filter((point: ChartPoint) => {
        const time = pointTime(point);

        if (time === null) {
            return true;
        }

        if (start !== null && time < start) {
            return false;
        }

        if (end !== null && time > end + 86_399_999) {
            return false;
        }

        return true;
    });
}

export default function TopsChartCard({
    title,
    description,
    chart,
    xAxisLabel = "Label",
    yAxisLabel,
    timeSeries = false,
}: {
    title: string;
    description: string;
    chart: ChartPayload | null | undefined;
    xAxisLabel?: string;
    yAxisLabel?: string;
    timeSeries?: boolean;
}) {
    const sourceSeries =
        chart?.series && chart.series.length > 0
            ? chart.series
            : [
                  {
                      label: title,
                      points: chart?.points ?? [],
                  },
              ];
    const [visibleSeries, setVisibleSeries] = React.useState<string[]>(
        sourceSeries.map((series: ChartSeries) => series.label),
    );
    const [startDate, setStartDate] = React.useState("");
    const [endDate, setEndDate] = React.useState("");
    const id = chartId(title);
    const yLabel = yAxisLabel ?? chart?.unit ?? "Value";
    const canFilterDates = timeSeries || Boolean(chart?.granularity);
    const series = sourceSeries
        .filter((item: ChartSeries) => visibleSeries.includes(item.label))
        .map((item: ChartSeries) => ({
            ...item,
            points: canFilterDates
                ? filteredPoints(item.points, startDate, endDate)
                : item.points,
        }));
    const hasData = series.some((item) => item.points.length > 0);
    const data: ReactChartSeries[] = series.map((item) => ({
        label: item.label,
        data: item.points.map((point) => ({
            label: point.label,
            value: Number(point.value ?? 0),
        })),
    }));
    const primaryAxis = React.useMemo(
        (): AxisOptions<ReactChartPoint> => ({
            getValue: (datum) => datum.label,
        }),
        [],
    );
    const secondaryAxes = React.useMemo(
        (): AxisOptions<ReactChartPoint>[] => [
            {
                getValue: (datum) => datum.value,
                elementType: "line",
            },
        ],
        [],
    );
    const rows = series.flatMap((item) =>
        item.points.map((point) => ({
            series: item.label,
            label: point.label,
            value: point.value,
        })),
    );

    return (
        <BasicCard>
            <section aria-labelledby={id} className="space-y-4">
                <div>
                    <h2 id={id} className="text-xl font-semibold">
                        {title}
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {description}
                    </p>
                </div>

                {sourceSeries.length > 1 ? (
                    <fieldset className="flex flex-wrap gap-3 text-sm">
                        <legend className="sr-only">
                            Toggle {title} series
                        </legend>
                        {sourceSeries.map((item: ChartSeries) => (
                            <label
                                key={item.label}
                                className="inline-flex items-center gap-2 rounded-sm border border-gray-200 px-3 py-2 font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300"
                            >
                                <input
                                    type="checkbox"
                                    checked={visibleSeries.includes(item.label)}
                                    onChange={(event) => {
                                        setVisibleSeries((current) =>
                                            event.target.checked
                                                ? [...current, item.label]
                                                : current.filter(
                                                      (label) =>
                                                          label !== item.label,
                                                  ),
                                        );
                                    }}
                                />
                                <span>{item.label}</span>
                            </label>
                        ))}
                    </fieldset>
                ) : null}

                {canFilterDates ? (
                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Start Date
                            <input
                                type="date"
                                className="mt-1 w-full rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                value={startDate}
                                onChange={(event) =>
                                    setStartDate(event.target.value)
                                }
                            />
                        </label>
                        <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            End Date
                            <input
                                type="date"
                                className="mt-1 w-full rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                value={endDate}
                                onChange={(event) =>
                                    setEndDate(event.target.value)
                                }
                            />
                        </label>
                    </div>
                ) : null}

                {hasData ? (
                    <div>
                        <div className="mb-2 flex justify-between text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                            <span>{xAxisLabel}</span>
                            <span>{yLabel}</span>
                        </div>
                        <div className="h-80 min-h-80 w-full overflow-hidden rounded-sm border border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-900">
                            <Chart
                                options={{
                                    data,
                                    primaryAxis,
                                    secondaryAxes,
                                    dark: true,
                                }}
                            />
                        </div>
                    </div>
                ) : (
                    <p className="rounded-sm bg-gray-100 p-3 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        No public chart data is available for the selected
                        filters.
                    </p>
                )}

                <details>
                    <summary className="cursor-pointer text-sm font-semibold text-gray-900 dark:text-gray-100">
                        Data table
                    </summary>
                    <div className="mt-3 overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead>
                                <tr>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        Series
                                    </th>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        {xAxisLabel}
                                    </th>
                                    <th className="border-b border-gray-200 py-2 dark:border-gray-700">
                                        {yLabel}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr
                                        key={`${title}-${row.series}-${row.label}`}
                                    >
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {row.series}
                                        </td>
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {row.label}
                                        </td>
                                        <td className="border-b border-gray-100 py-2 dark:border-gray-700">
                                            {formatNumber(
                                                Number(row.value ?? 0),
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>
        </BasicCard>
    );
}
