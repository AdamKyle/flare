import React from "react";
import {
    formatDuration,
    formatPercent,
    formatTopsCompactValue,
    formatTopsValue,
} from "../helpers/tops-format-value";
import TopsStatListItem from "../types/tops-stat-list-item";
import TopsStatListProps from "../types/tops-stat-list-props";

export default class TopsStatList extends React.Component<TopsStatListProps> {
    formatItemValue(item: TopsStatListItem): string {
        if (item.type === "duration" && typeof item.value === "number") {
            return formatDuration(item.value);
        }

        if (item.type === "percent" && typeof item.value === "number") {
            return formatPercent(item.value);
        }

        if (this.props.compact) {
            return formatTopsCompactValue(item.value);
        }

        return formatTopsValue(item.value);
    }

    listClasses(): string {
        return "grid grid-cols-1 gap-3 sm:grid-cols-2";
    }

    dtClasses(): string {
        return (
            "whitespace-nowrap text-sm font-semibold text-gray-700 dark:text-gray-300 " +
            (this.props.accentClassName ?? "")
        );
    }

    ddClasses(): string {
        return "whitespace-nowrap text-gray-900 tabular-nums dark:text-gray-100";
    }

    render() {
        return (
            <dl className={this.listClasses()}>
                {this.props.items.map((item: TopsStatListItem) => (
                    <React.Fragment key={item.label}>
                        <dt className={this.dtClasses()}>{item.label}</dt>
                        <dd className={this.ddClasses()}>
                            {this.formatItemValue(item)}
                        </dd>
                    </React.Fragment>
                ))}
            </dl>
        );
    }
}
