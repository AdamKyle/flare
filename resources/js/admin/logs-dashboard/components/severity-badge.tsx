import React from "react";
import SeverityBadgeProps from "../types/severity-badge-props";

const severityColors: Record<string, string> = {
    emergency: "bg-red-700 text-white",
    alert: "bg-red-600 text-white",
    critical: "bg-red-500 text-white",
    error: "bg-red-400 text-white",
    fatal: "bg-red-700 text-white",
    warning: "bg-yellow-400 text-gray-900",
    notice: "bg-blue-400 text-white",
    info: "bg-blue-300 text-gray-900",
    debug: "bg-gray-300 text-gray-800",
    unknown: "bg-gray-200 text-gray-700",
};

export default class SeverityBadge extends React.Component<SeverityBadgeProps> {
    render() {
        const severityClass =
            severityColors[this.props.severity.toLowerCase()] ??
            severityColors.unknown;

        return (
            <span
                className={
                    "inline-block rounded px-1.5 py-0.5 text-xs " +
                    `font-semibold uppercase ${severityClass}`
                }
            >
                {this.props.severity}
            </span>
        );
    }
}
