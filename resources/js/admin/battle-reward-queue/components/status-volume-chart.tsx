import React from "react";
import { ChartPoint } from "../types/reward-queue";
import ChartDataTableProps from "../types/chart-data-table-props";
import ChartDataTableState from "../types/chart-data-table-state";
import InlineSvgChartProps from "../types/inline-svg-chart-props";
import StatusVolumeChartProps from "../types/status-volume-chart-props";
import RewardQueueCard from "./reward-queue-card";

const SERIES = [
    { key: "completed", label: "Completed", color: "#22c55e", dash: "" },
    { key: "failed", label: "Failed", color: "#ef4444", dash: "6,3" },
    { key: "pending", label: "Pending", color: "#f59e0b", dash: "2,2" },
    {
        key: "processing",
        label: "Processing",
        color: "#3b82f6",
        dash: "8,2,2,2",
    },
    {
        key: "resumable",
        label: "Resumable",
        color: "#8b5cf6",
        dash: "4,4",
    },
] as const;

const CHART_H = 200;
const CHART_W = 600;
const PAD_L = 48;
const PAD_R = 16;
const PAD_T = 12;
const PAD_B = 36;
const INNER_W = CHART_W - PAD_L - PAD_R;
const INNER_H = CHART_H - PAD_T - PAD_B;
const TABLE_PAGE = 10;
const MAX_X_LABELS = 8;

function sparseLabels(periods: string[]): number[] {
    const count = periods.length;

    if (count <= MAX_X_LABELS) {
        return periods.map((_, index: number) => index);
    }

    const step = Math.ceil(count / MAX_X_LABELS);
    const indices: number[] = [];

    for (let index = 0; index < count; index += step) {
        indices.push(index);
    }

    if (indices[indices.length - 1] !== count - 1) {
        indices.push(count - 1);
    }

    return indices;
}

function niceYTicks(max: number): number[] {
    if (max === 0) {
        return [0, 1, 2, 3, 4];
    }

    const raw = max / 4;
    const magnitude = Math.pow(10, Math.floor(Math.log10(raw)));
    const nice = Math.ceil(raw / magnitude) * magnitude;

    return [0, nice, nice * 2, nice * 3, nice * 4];
}

function shortLabel(period: string): string {
    if (period.includes(" ")) {
        const parts = period.split(" ");

        return parts[1]?.substring(0, 5) ?? period;
    }

    if (period.length > 8) {
        return period.substring(5);
    }

    return period;
}

class InlineSvgChart extends React.Component<InlineSvgChartProps> {
    periods() {
        return this.props.points.map((point: ChartPoint) => point.period);
    }

    maxValue() {
        return this.props.points.reduce(
            (value: number, point: ChartPoint) =>
                Math.max(
                    value,
                    point.completed,
                    point.failed,
                    point.pending,
                    point.processing,
                    point.resumable ?? 0,
                ),
            0,
        );
    }

    xPos(index: number) {
        return (
            PAD_L + (index / Math.max(this.periods().length - 1, 1)) * INNER_W
        );
    }

    yPos(value: number, yMax: number) {
        return PAD_T + INNER_H - (value / Math.max(yMax, 1)) * INNER_H;
    }

    renderSeries(yMax: number) {
        return SERIES.map(({ key, color, dash }) => {
            const path = this.props.points
                .map((point: ChartPoint, index: number) => {
                    const value =
                        (point[key as keyof ChartPoint] as
                            | number
                            | undefined) ?? 0;

                    return `${index === 0 ? "M" : "L"} ${this.xPos(index)} ${this.yPos(value, yMax)}`;
                })
                .join(" ");

            return (
                <path
                    key={key}
                    d={path}
                    fill="none"
                    stroke={color}
                    strokeWidth={2}
                    strokeDasharray={dash || undefined}
                />
            );
        });
    }

    render() {
        const periods = this.periods();
        const yTicks = niceYTicks(this.maxValue());
        const yMax = yTicks[yTicks.length - 1] ?? 4;
        const xLabelIndices = sparseLabels(periods);

        return (
            <svg
                viewBox={`0 0 ${CHART_W} ${CHART_H}`}
                className="min-h-[140px] w-full"
                role="img"
                aria-hidden="true"
            >
                {yTicks.map((tick: number) => (
                    <g key={tick}>
                        <line
                            x1={PAD_L}
                            x2={PAD_L + INNER_W}
                            y1={this.yPos(tick, yMax)}
                            y2={this.yPos(tick, yMax)}
                            stroke="currentColor"
                            strokeOpacity={0.15}
                            strokeWidth={1}
                        />
                        <text
                            x={PAD_L - 4}
                            y={this.yPos(tick, yMax) + 4}
                            textAnchor="end"
                            fontSize={10}
                            fill="currentColor"
                            opacity={0.6}
                        >
                            {tick}
                        </text>
                    </g>
                ))}
                {this.renderSeries(yMax)}
                {xLabelIndices.map((index: number) => (
                    <text
                        key={index}
                        x={this.xPos(index)}
                        y={PAD_T + INNER_H + 16}
                        textAnchor="middle"
                        fontSize={9}
                        fill="currentColor"
                        opacity={0.65}
                    >
                        {shortLabel(periods[index] ?? "")}
                    </text>
                ))}
                <line
                    x1={PAD_L}
                    x2={PAD_L + INNER_W}
                    y1={PAD_T + INNER_H}
                    y2={PAD_T + INNER_H}
                    stroke="currentColor"
                    strokeOpacity={0.3}
                    strokeWidth={1}
                />
                <line
                    x1={PAD_L}
                    x2={PAD_L}
                    y1={PAD_T}
                    y2={PAD_T + INNER_H}
                    stroke="currentColor"
                    strokeOpacity={0.3}
                    strokeWidth={1}
                />
            </svg>
        );
    }
}

class ChartLegend extends React.Component {
    render() {
        return (
            <div
                className="mt-3 flex flex-wrap gap-4 text-xs"
                aria-label="Chart legend"
            >
                {SERIES.map(({ key, label, color }) => (
                    <div key={key} className="flex items-center gap-1.5">
                        <span
                            className="inline-block h-2 w-6 rounded-sm"
                            style={{ backgroundColor: color }}
                            aria-hidden="true"
                        />
                        <span className="capitalize text-gray-700 dark:text-gray-300">
                            {label}
                        </span>
                    </div>
                ))}
            </div>
        );
    }
}

class ChartDataTable extends React.Component<
    ChartDataTableProps,
    ChartDataTableState
> {
    public constructor(props: ChartDataTableProps) {
        super(props);

        this.state = {
            page: 1,
        };
    }

    totalPages() {
        return Math.max(1, Math.ceil(this.props.points.length / TABLE_PAGE));
    }

    rows() {
        return this.props.points.slice(
            (this.state.page - 1) * TABLE_PAGE,
            this.state.page * TABLE_PAGE,
        );
    }

    changePage(page: number) {
        this.setState({
            page,
        });
    }

    render() {
        return (
            <details className="mt-3 text-sm text-gray-700 dark:text-gray-200">
                <summary className="cursor-pointer font-medium">
                    View chart data table
                </summary>
                <div className="mt-2 overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <thead>
                            <tr className="border-b dark:border-gray-700">
                                <th scope="col" className="p-2">
                                    Period
                                </th>
                                <th scope="col" className="p-2">
                                    Completed
                                </th>
                                <th scope="col" className="p-2">
                                    Failed
                                </th>
                                <th scope="col" className="p-2">
                                    Pending
                                </th>
                                <th scope="col" className="p-2">
                                    Processing
                                </th>
                                <th scope="col" className="p-2">
                                    Resumable
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {this.rows().map((point: ChartPoint) => (
                                <tr
                                    className="border-t dark:border-gray-700"
                                    key={point.period}
                                >
                                    <td className="p-2">{point.period}</td>
                                    <td className="p-2">{point.completed}</td>
                                    <td className="p-2">{point.failed}</td>
                                    <td className="p-2">{point.pending}</td>
                                    <td className="p-2">{point.processing}</td>
                                    <td className="p-2">
                                        {point.resumable ?? 0}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {this.totalPages() > 1 && (
                    <div className="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <span className="text-gray-600 dark:text-gray-300">
                            Page {this.state.page} of {this.totalPages()}
                        </span>
                        <div className="flex gap-2">
                            <button
                                className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                                disabled={this.state.page <= 1}
                                onClick={() =>
                                    this.changePage(this.state.page - 1)
                                }
                            >
                                Previous
                            </button>
                            <button
                                className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                                disabled={this.state.page >= this.totalPages()}
                                onClick={() =>
                                    this.changePage(this.state.page + 1)
                                }
                            >
                                Next
                            </button>
                        </div>
                    </div>
                )}
            </details>
        );
    }
}

export default class StatusVolumeChart extends React.Component<StatusVolumeChartProps> {
    total() {
        return this.props.points.reduce(
            (value: number, point: ChartPoint) =>
                value +
                point.completed +
                point.failed +
                point.pending +
                point.processing,
            0,
        );
    }

    totalFor(key: keyof ChartPoint) {
        return this.props.points.reduce((value: number, point: ChartPoint) => {
            const pointValue = point[key];

            return value + (typeof pointValue === "number" ? pointValue : 0);
        }, 0);
    }

    summary() {
        return `${this.props.title}: ${this.total()} total requests — completed ${this.totalFor("completed")}, failed ${this.totalFor("failed")}, pending ${this.totalFor("pending")}, processing ${this.totalFor("processing")}.`;
    }

    render() {
        return (
            <RewardQueueCard
                title={this.props.title}
                description={this.props.description}
            >
                {this.props.points.length === 0 ? (
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        No requests in this period.
                    </p>
                ) : (
                    <>
                        <p className="sr-only">{this.summary()}</p>
                        <InlineSvgChart points={this.props.points} />
                        <ChartLegend />
                        <ChartDataTable points={this.props.points} />
                    </>
                )}
            </RewardQueueCard>
        );
    }
}
