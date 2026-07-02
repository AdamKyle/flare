import React from "react";
import { formatTopsValue } from "../helpers/tops-format-value";
import TopsStatListItem from "../types/tops-stat-list-item";
import TopsStatListProps from "../types/tops-stat-list-props";

export default class TopsStatList extends React.Component<TopsStatListProps> {
    render() {
        return (
            <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {this.props.items.map((item: TopsStatListItem) => (
                    <div key={item.label}>
                        <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {item.label}
                        </dt>
                        <dd className="text-gray-900 dark:text-gray-100">
                            {formatTopsValue(item.value)}
                        </dd>
                    </div>
                ))}
            </dl>
        );
    }
}
