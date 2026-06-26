import React, { useMemo, useState } from "react";

export type MonitoringChartPoint = {
    period: string;
    [key: string]: string | number;
};

type Series = {
    key: string;
    label: string;
    color: string;
    dash?: string;
};

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
    if (periods.length <= MAX_X_LABELS) {
        return periods.map((_, index) => index);
    }

    const step = Math.ceil(periods.length / MAX_X_LABELS);
    const indices: number[] = [];

    for (let index = 0; index < periods.length; index += step) {
        indices.push(index);
    }

    if (indices[indices.length - 1] !== periods.length - 1) {
        indices.push(periods.length - 1);
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
    if (period.length > 8) {
        return period.substring(5);
    }

    return period;
}

function valueFor(point: MonitoringChartPoint, key: string): number {
    const value = point[key];

    return typeof value === "number" ? value : 0;
}

export default function MonitoringStatusChart({
    title,
    description,
    points,
    series,
}: {
    title: string;
    description: string;
    points: MonitoringChartPoint[];
    series: Series[];
}) {
    const [page, setPage] = useState(1);
    const periods = points.map((point) => point.period);
    const maxValue = points.reduce((maximum, point) => {
        return Math.max(
            maximum,
            ...series.map((item) => valueFor(point, item.key)),
        );
    }, 0);
    const yTicks = useMemo(() => niceYTicks(maxValue), [maxValue]);
    const yMax = yTicks[yTicks.length - 1] ?? 4;
    const xLabelIndices = useMemo(() => sparseLabels(periods), [periods]);
    const totalPages = Math.max(1, Math.ceil(points.length / TABLE_PAGE));
    const rows = points.slice((page - 1) * TABLE_PAGE, page * TABLE_PAGE);

    const xPos = (index: number) =>
        PAD_L + (index / Math.max(periods.length - 1, 1)) * INNER_W;
    const yPos = (value: number) =>
        PAD_T + INNER_H - (value / Math.max(yMax, 1)) * INNER_H;

    return (
        <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                {title}
            </h2>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                {description}
            </p>
            {points.length === 0 ? (
                <p className="mt-3 text-sm text-gray-500">
                    No data in this period.
                </p>
            ) : (
                <>
                    <svg
                        viewBox={`0 0 ${CHART_W} ${CHART_H}`}
                        className="mt-3 w-full"
                        style={{ minHeight: 140 }}
                        role="img"
                        aria-hidden="true"
                    >
                        {yTicks.map((tick) => (
                            <g key={tick}>
                                <line
                                    x1={PAD_L}
                                    x2={PAD_L + INNER_W}
                                    y1={yPos(tick)}
                                    y2={yPos(tick)}
                                    stroke="currentColor"
                                    strokeOpacity={0.15}
                                    strokeWidth={1}
                                />
                                <text
                                    x={PAD_L - 4}
                                    y={yPos(tick) + 4}
                                    textAnchor="end"
                                    fontSize={10}
                                    fill="currentColor"
                                    opacity={0.6}
                                >
                                    {tick}
                                </text>
                            </g>
                        ))}
                        {series.map(({ key, color, dash }) => {
                            const path = points
                                .map((point, index) => {
                                    return `${index === 0 ? "M" : "L"} ${xPos(index)} ${yPos(valueFor(point, key))}`;
                                })
                                .join(" ");

                            return (
                                <path
                                    key={key}
                                    d={path}
                                    fill="none"
                                    stroke={color}
                                    strokeWidth={2}
                                    strokeDasharray={dash}
                                />
                            );
                        })}
                        {xLabelIndices.map((index) => (
                            <text
                                key={index}
                                x={xPos(index)}
                                y={PAD_T + INNER_H + 16}
                                textAnchor="middle"
                                fontSize={9}
                                fill="currentColor"
                                opacity={0.65}
                            >
                                {shortLabel(periods[index] ?? "")}
                            </text>
                        ))}
                    </svg>
                    <div className="mt-3 flex flex-wrap gap-4 text-xs">
                        {series.map(({ key, label, color }) => (
                            <div
                                key={key}
                                className="flex items-center gap-1.5"
                            >
                                <span
                                    className="inline-block h-2 w-6 rounded-sm"
                                    style={{ backgroundColor: color }}
                                />
                                <span className="text-gray-700 dark:text-gray-300">
                                    {label}
                                </span>
                            </div>
                        ))}
                    </div>
                    <details className="mt-3 text-sm text-gray-700 dark:text-gray-200">
                        <summary className="cursor-pointer font-medium">
                            View chart data table
                        </summary>
                        <div className="mt-2 overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b dark:border-gray-700">
                                        <th className="p-2">Period</th>
                                        {series.map((item) => (
                                            <th key={item.key} className="p-2">
                                                {item.label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((point) => (
                                        <tr
                                            className="border-t dark:border-gray-700"
                                            key={point.period}
                                        >
                                            <td className="p-2">
                                                {point.period}
                                            </td>
                                            {series.map((item) => (
                                                <td
                                                    key={item.key}
                                                    className="p-2"
                                                >
                                                    {valueFor(point, item.key)}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span className="text-gray-600 dark:text-gray-300">
                                Page {page} of {totalPages}
                            </span>
                            <div className="flex gap-2">
                                <button
                                    className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                                    disabled={page <= 1}
                                    onClick={() =>
                                        setPage((current) => current - 1)
                                    }
                                >
                                    Previous
                                </button>
                                <button
                                    className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                                    disabled={page >= totalPages}
                                    onClick={() =>
                                        setPage((current) => current + 1)
                                    }
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </details>
                </>
            )}
        </section>
    );
}
