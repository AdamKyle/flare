import React from "react";
import { ChartPayload, ChartPoint } from "../types/admin-statistics-dashboard";

interface RankedTableProps {
    title: string;
    description: string;
    chart: ChartPayload;
    valueLabel: string;
}

export default class RankedTable extends React.Component<RankedTableProps> {
    private tableId(): string {
        return this.props.title.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    }

    private points(): ChartPoint[] {
        if (this.props.chart.points && this.props.chart.points.length > 0) {
            return this.props.chart.points;
        }

        if (this.props.chart.series && this.props.chart.series.length > 0) {
            return this.props.chart.series[0].points;
        }

        return [];
    }

    render() {
        const points = this.points();

        return (
            <section
                className="rounded-sm bg-white p-4 shadow dark:bg-gray-800"
                aria-labelledby={this.tableId()}
            >
                <h2
                    id={this.tableId()}
                    className="text-lg font-semibold text-gray-900 dark:text-gray-100"
                >
                    {this.props.title}
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {this.props.description}
                </p>

                {points.length === 0 ? (
                    <p className="mt-4 rounded-sm bg-gray-100 p-3 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        No database records are available for this metric yet.
                    </p>
                ) : (
                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <caption className="sr-only">
                                {this.props.title} ranked data
                            </caption>
                            <thead>
                                <tr>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        Rank
                                    </th>
                                    <th className="border-b border-gray-200 py-2 pr-4 dark:border-gray-700">
                                        Name
                                    </th>
                                    <th className="border-b border-gray-200 py-2 dark:border-gray-700">
                                        {this.props.valueLabel}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {points.map((point, index) => (
                                    <tr
                                        key={`${this.props.title}-${point.label}`}
                                    >
                                        <td className="border-b border-gray-100 py-2 pr-4 dark:border-gray-700">
                                            {index + 1}
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
                )}
            </section>
        );
    }
}
