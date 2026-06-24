import React from "react";

type BarChartPoint = {
    period: string;
    value: number;
};

const BAR_H = 160;
const BAR_W = 600;
const PAD_L = 48;
const PAD_R = 16;
const PAD_T = 10;
const PAD_B = 32;
const INNER_W = BAR_W - PAD_L - PAD_R;
const INNER_H = BAR_H - PAD_T - PAD_B;
const MAX_LABELS = 8;

function sparseLabels(n: number): number[] {
    if (n <= MAX_LABELS) {
        return Array.from({ length: n }, (_, i) => i);
    }
    const step = Math.ceil(n / MAX_LABELS);
    const indices: number[] = [];
    for (let i = 0; i < n; i += step) {
        indices.push(i);
    }
    if (indices[indices.length - 1] !== n - 1) {
        indices.push(n - 1);
    }
    return indices;
}

export default function SimpleBarChart({
    title,
    points,
    color = "#3b82f6",
}: {
    title: string;
    points: BarChartPoint[];
    color?: string;
}) {
    const maxVal = Math.max(...points.map((p) => p.value), 1);
    const n = points.length;
    const barW = n > 0 ? Math.max(2, INNER_W / n - 2) : 0;
    const labelIndices = sparseLabels(n);

    const xCenter = (i: number) => PAD_L + (i / Math.max(n - 1, 1)) * INNER_W;
    const barH = (v: number) => (v / maxVal) * INNER_H;
    const barX = (i: number) =>
        PAD_L + (INNER_W * i) / Math.max(n, 1) - barW / 2;
    const barY = (v: number) => PAD_T + INNER_H - barH(v);

    return (
        <div>
            <p className="mb-1 text-sm font-medium text-gray-800 dark:text-gray-200">
                {title}
            </p>
            {points.length === 0 ? (
                <p className="text-sm text-gray-500">No data in this period.</p>
            ) : (
                <svg
                    viewBox={`0 0 ${BAR_W} ${BAR_H}`}
                    className="w-full"
                    style={{ minHeight: 100 }}
                    role="img"
                    aria-label={title}
                >
                    <line
                        x1={PAD_L}
                        x2={PAD_L + INNER_W}
                        y1={PAD_T + INNER_H}
                        y2={PAD_T + INNER_H}
                        stroke="currentColor"
                        strokeOpacity={0.25}
                        strokeWidth={1}
                    />
                    {points.map((p, i) => (
                        <rect
                            key={p.period}
                            x={barX(i)}
                            y={barY(p.value)}
                            width={barW}
                            height={barH(p.value)}
                            fill={color}
                            opacity={0.8}
                        >
                            <title>
                                {p.period}: {p.value}
                            </title>
                        </rect>
                    ))}
                    {labelIndices.map((i) => (
                        <text
                            key={i}
                            x={xCenter(i)}
                            y={PAD_T + INNER_H + 14}
                            textAnchor="middle"
                            fontSize={9}
                            fill="currentColor"
                            opacity={0.6}
                        >
                            {points[i]?.period.substring(5) ?? ""}
                        </text>
                    ))}
                </svg>
            )}
        </div>
    );
}
