import React from "react";
import { AxisOptions, Chart } from "react-charts";
import {
    ChartPayload,
    ChartPoint,
    ChartSeries,
} from "../types/admin-statistics-dashboard";

interface ChartCardProps {
    title: string;
    description: string;
    chart: ChartPayload;
}

interface ReactChartPoint {
    label: string;
    value: number;
}

interface ReactChartSeries {
    label: string;
    data: ReactChartPoint[];
}

export default class ChartCard extends React.Component<ChartCardProps> {
    private primaryAxis: AxisOptions<ReactChartPoint> = {
        getValue: (datum: ReactChartPoint) => datum.label,
    };

    private secondaryAxes: AxisOptions<ReactChartPoint>[] = [
        {
            getValue: (datum: ReactChartPoint) => datum.value,
            elementType: "line",
        },
    ];

    private chartId(): string {
        return this.props.title.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    }

    private chartSeries(): ChartSeries[] {
        if (this.props.chart.series && this.props.chart.series.length > 0) {
            return this.props.chart.series;
        }

        return [
            {
                label: this.props.title,
                points: this.props.chart.points ?? [],
            },
        ];
    }

    private reactChartData(): ReactChartSeries[] {
        return this.chartSeries().map((series: ChartSeries) => ({
            label: series.label,
            data: series.points.map((point: ChartPoint) => ({
                label: point.label,
                value: point.value,
            })),
        }));
    }

    private hasData(): boolean {
        return this.chartSeries().some(
            (series: ChartSeries) => series.points.length > 0,
        );
    }

    private tableRows(): Array<ChartPoint & { seriesLabel: string }> {
        const rows: Array<ChartPoint & { seriesLabel: string }> = [];

        this.chartSeries().forEach((series: ChartSeries) => {
            series.points.forEach((point: ChartPoint) => {
                rows.push({
                    seriesLabel: series.label,
                    label: point.label,
                    value: point.value,
                });
            });
        });

        return rows;
    }

    render() {
        const chartData = this.reactChartData();

        return (
            <section
                className="rounded-sm bg-white p-4 shadow dark:bg-gray-800"
                aria-labelledby={this.chartId()}
            >
                <h2
                    id={this.chartId()}
                    className="text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    {this.props.title}
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {this.props.description}
                </p>
                <p className="sr-only">
                    {this.props.title} line chart using{" "}
                    {this.props.chart.source}. Values are shown in{" "}
                    {this.props.chart.unit}.
                </p>

                {this.hasData() ? (
                    <div className="mt-4 h-80 min-h-80 w-full overflow-hidden">
                        <Chart
                            options={{
                                data: chartData,
                                primaryAxis: this.primaryAxis,
                                secondaryAxes: this.secondaryAxes,
                            }}
                        />
                    </div>
                ) : (
                    <p className="mt-4 rounded-sm bg-gray-100 p-3 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        No database records are available for this metric yet.
                    </p>
                )}

                <details className="mt-4">
                    <summary className="cursor-pointer text-sm font-semibold text-blue-700 dark:text-blue-300">
                        Data table
                    </summary>
                    <div className="mt-3 overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <caption className="sr-only">
                                {this.props.title} source data
                            </caption>
                            <thead>
                                <tr>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        Series
                                    </th>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        Label
                                    </th>
                                    <th className="border-b border-gray-200 py-2 dark:border-gray-700">
                                        Value
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {this.tableRows().map((point) => (
                                    <tr
                                        key={`${this.props.title}-${point.seriesLabel}-${point.label}`}
                                    >
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {point.seriesLabel}
                                        </td>
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {point.label}
                                        </td>
                                        <td className="border-b border-gray-100 py-2 dark:border-gray-700">
                                            {point.value.toLocaleString()}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </details>
            </section>
        );
    }
}
