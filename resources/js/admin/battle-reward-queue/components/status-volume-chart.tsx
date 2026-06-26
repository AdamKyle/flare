import React, { useMemo, useState } from "react";
import { ChartPoint } from "../types/reward-queue";
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
    const n = periods.length;
    if (n <= MAX_X_LABELS) {
        return periods.map((_, i) => i);
    }
    const step = Math.ceil(n / MAX_X_LABELS);
    const indices: number[] = [];
    for (let i = 0; i < n; i += step) {
        indices.push(i);
    }
    if (indices[indices.length - 1] !== n - 1) {
        indices.push(n - 1);
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

function InlineSvgChart({ points }: { points: ChartPoint[] }) {
    const periods = points.map((p) => p.period);
    const maxValue = points.reduce(
        (acc, p) =>
            Math.max(
                acc,
                p.completed,
                p.failed,
                p.pending,
                p.processing,
                p.resumable ?? 0,
            ),
        0,
    );
    const yTicks = useMemo(() => niceYTicks(maxValue), [maxValue]);
    const yMax = yTicks[yTicks.length - 1] ?? 4;
    const xLabelIndices = useMemo(() => sparseLabels(periods), [periods]);

    const xPos = (i: number) =>
        PAD_L + (i / Math.max(periods.length - 1, 1)) * INNER_W;
    const yPos = (v: number) =>
        PAD_T + INNER_H - (v / Math.max(yMax, 1)) * INNER_H;

    return (
        <svg
            viewBox={`0 0 ${CHART_W} ${CHART_H}`}
            className="w-full"
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

            {SERIES.map(({ key, color, dash }) => {
                const d = points
                    .map((p, i) => {
                        const v =
                            (p[key as keyof ChartPoint] as
                                | number
                                | undefined) ?? 0;
                        return `${i === 0 ? "M" : "L"} ${xPos(i)} ${yPos(v)}`;
                    })
                    .join(" ");
                return (
                    <path
                        key={key}
                        d={d}
                        fill="none"
                        stroke={color}
                        strokeWidth={2}
                        strokeDasharray={dash || undefined}
                    />
                );
            })}

            {xLabelIndices.map((i) => (
                <text
                    key={i}
                    x={xPos(i)}
                    y={PAD_T + INNER_H + 16}
                    textAnchor="middle"
                    fontSize={9}
                    fill="currentColor"
                    opacity={0.65}
                >
                    {shortLabel(periods[i] ?? "")}
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

function ChartLegend() {
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

function ChartDataTable({ points }: { points: ChartPoint[] }) {
    const [page, setPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(points.length / TABLE_PAGE));
    const slice = points.slice((page - 1) * TABLE_PAGE, page * TABLE_PAGE);

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
                        {slice.map((point) => (
                            <tr
                                className="border-t dark:border-gray-700"
                                key={point.period}
                            >
                                <td className="p-2">{point.period}</td>
                                <td className="p-2">{point.completed}</td>
                                <td className="p-2">{point.failed}</td>
                                <td className="p-2">{point.pending}</td>
                                <td className="p-2">{point.processing}</td>
                                <td className="p-2">{point.resumable ?? 0}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {totalPages > 1 && (
                <div className="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <span className="text-gray-600 dark:text-gray-300">
                        Page {page} of {totalPages}
                    </span>
                    <div className="flex gap-2">
                        <button
                            className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                            disabled={page <= 1}
                            onClick={() => setPage((p) => p - 1)}
                        >
                            Previous
                        </button>
                        <button
                            className="rounded border border-gray-300 px-2 py-1 disabled:opacity-40 dark:border-gray-600"
                            disabled={page >= totalPages}
                            onClick={() => setPage((p) => p + 1)}
                        >
                            Next
                        </button>
                    </div>
                </div>
            )}
        </details>
    );
}

export default function StatusVolumeChart({
    title,
    description,
    points,
}: {
    title: string;
    description: string;
    points: ChartPoint[];
}) {
    const total = points.reduce(
        (acc, p) => acc + p.completed + p.failed + p.pending + p.processing,
        0,
    );
    const summary = `${title}: ${total} total requests — completed ${points.reduce((a, p) => a + p.completed, 0)}, failed ${points.reduce((a, p) => a + p.failed, 0)}, pending ${points.reduce((a, p) => a + p.pending, 0)}, processing ${points.reduce((a, p) => a + p.processing, 0)}.`;

    return (
        <RewardQueueCard title={title} description={description}>
            {points.length === 0 ? (
                <p className="text-sm text-gray-600 dark:text-gray-300">
                    No requests in this period.
                </p>
            ) : (
                <>
                    <p className="sr-only">{summary}</p>
                    <InlineSvgChart points={points} />
                    <ChartLegend />
                    <ChartDataTable points={points} />
                </>
            )}
        </RewardQueueCard>
    );
}
