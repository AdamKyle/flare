import React from "react";
import { RequestFiltersType } from "../types/reward-queue";
import RequestFiltersProps from "../types/request-filters-props";
import { rewardQueueFilterOptions } from "../values/reward-queue-filter-options";

export default class RequestFilters extends React.Component<RequestFiltersProps> {
    updateFilter(name: string, value: string) {
        this.props.onChange({
            ...this.props.filters,
            [name]: value,
        });
    }

    renderFilter(name: string, value: string) {
        const options =
            rewardQueueFilterOptions[
                name as keyof typeof rewardQueueFilterOptions
            ];
        const label = name.replaceAll("_", " ");

        return (
            <label className="text-sm font-medium capitalize" key={name}>
                {label}
                {options ? (
                    <select
                        className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                        value={value}
                        onChange={(event) =>
                            this.updateFilter(name, event.target.value)
                        }
                    >
                        <option value="">All</option>
                        {options.map((option) => (
                            <option value={option.value} key={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                ) : (
                    <input
                        className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                        type={name.startsWith("date_") ? "date" : "text"}
                        value={value}
                        onChange={(event) =>
                            this.updateFilter(name, event.target.value)
                        }
                    />
                )}
            </label>
        );
    }

    render() {
        return (
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {Object.entries(this.props.filters).map(([name, value]) =>
                    this.renderFilter(name, value),
                )}
            </div>
        );
    }
}
