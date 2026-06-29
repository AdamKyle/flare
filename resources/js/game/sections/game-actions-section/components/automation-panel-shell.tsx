import React, { ReactNode, useId, useState } from "react";

type AutomationPanelShellProps = {
    title: string;
    timerText?: string;
    statusText?: string;
    defaultExpanded?: boolean;
    children: ReactNode;
};

export default function AutomationPanelShell({
    title,
    timerText,
    statusText,
    defaultExpanded = true,
    children,
}: AutomationPanelShellProps) {
    const [isExpanded, setIsExpanded] = useState(defaultExpanded);
    const bodyId = useId();

    return (
        <section className="w-full overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <button
                type="button"
                className="w-full cursor-pointer border-b border-gray-200 bg-gray-100 px-4 py-3 text-left text-gray-700 hover:bg-gray-200 focus:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:bg-gray-600 dark:focus:ring-gray-500"
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
                            <span className="rounded bg-gray-200 px-2 py-0.5 font-medium capitalize text-gray-800 dark:bg-gray-800 dark:text-gray-200">
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
                    className="p-4"
                >
                    {children}
                </div>
            ) : null}
        </section>
    );
}
