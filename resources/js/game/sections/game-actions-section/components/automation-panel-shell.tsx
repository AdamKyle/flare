import React, { ReactNode, useId, useState } from "react";

export type AutomationPanelTone = "neutral" | "success" | "warning";

type ToneClasses = {
    section: string;
    header: string;
    badge: string;
    body: string;
};

const TONE_CLASSES: Record<AutomationPanelTone, ToneClasses> = {
    neutral: {
        section: "border-gray-200 dark:border-gray-700",
        header: "border-gray-200 bg-gray-100 text-gray-700 hover:bg-gray-200 focus:bg-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:bg-gray-600",
        badge: "bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-200",
        body: "bg-white dark:bg-gray-900",
    },
    success: {
        section: "border-green-300 dark:border-green-700",
        header: "border-green-300 bg-green-100 text-green-800 hover:bg-green-200 focus:bg-green-200 dark:border-green-700 dark:bg-green-900/60 dark:text-green-300 dark:hover:bg-green-900 dark:focus:bg-green-900",
        badge: "bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200",
        body: "bg-green-50/40 dark:bg-green-950/20",
    },
    warning: {
        section: "border-orange-500 dark:border-orange-400",
        header: "border-orange-500 bg-orange-100 text-orange-700 hover:bg-orange-200 focus:bg-orange-200 dark:border-orange-400 dark:bg-orange-900/60 dark:text-orange-300 dark:hover:bg-orange-900 dark:focus:bg-orange-900",
        badge: "bg-orange-200 text-orange-800 dark:bg-orange-800 dark:text-orange-200",
        body: "bg-orange-50/40 dark:bg-orange-950/20",
    },
};

type AutomationPanelShellProps = {
    title: string;
    timerText?: string;
    statusText?: string;
    defaultExpanded?: boolean;
    tone?: AutomationPanelTone;
    children: ReactNode;
};

export default function AutomationPanelShell({
    title,
    timerText,
    statusText,
    defaultExpanded = true,
    tone = "neutral",
    children,
}: AutomationPanelShellProps) {
    const [isExpanded, setIsExpanded] = useState(defaultExpanded);
    const bodyId = useId();
    const toneClasses = TONE_CLASSES[tone];

    return (
        <section
            className={
                "w-full overflow-hidden rounded-lg border bg-white dark:bg-gray-900 " +
                toneClasses.section
            }
        >
            <button
                type="button"
                className={
                    "w-full cursor-pointer border-b px-4 py-3 text-left focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-300 dark:focus:ring-gray-500 " +
                    toneClasses.header
                }
                aria-expanded={isExpanded}
                aria-controls={bodyId}
                onClick={() => setIsExpanded(!isExpanded)}
            >
                <span className="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <span className="font-bold text-sm uppercase tracking-wide">
                        {title}
                    </span>
                    <span className="flex flex-wrap items-center gap-2 text-xs">
                        {statusText ? (
                            <span
                                className={
                                    "rounded px-2 py-0.5 font-medium capitalize " +
                                    toneClasses.badge
                                }
                            >
                                {statusText}
                            </span>
                        ) : null}
                        {timerText ? (
                            <span className="rounded bg-orange-100 px-2 py-0.5 font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                {timerText}
                            </span>
                        ) : null}
                        <span aria-hidden="true">{isExpanded ? "▲" : "▼"}</span>
                        <span className="sr-only">
                            {isExpanded ? "Collapse" : "Expand"} {title}
                        </span>
                    </span>
                </span>
            </button>

            {isExpanded ? (
                <div
                    id={bodyId}
                    role="region"
                    aria-label={title}
                    className={"p-4 " + toneClasses.body}
                >
                    {children}
                </div>
            ) : null}
        </section>
    );
}
