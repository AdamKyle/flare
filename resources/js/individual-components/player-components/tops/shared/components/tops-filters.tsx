import React from "react";
import TopsFiltersProps from "../types/tops-filters-props";

export default class TopsFilters extends React.Component<TopsFiltersProps> {
    render() {
        return (
            <div className="grid gap-4 lg:grid-cols-3">
                <label className="block">
                    <span className="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Search
                    </span>
                    <input
                        className="w-full rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        type="search"
                        placeholder="Search character name"
                        value={this.props.search}
                        onChange={(event) =>
                            this.props.onSearch(event.target.value)
                        }
                    />
                </label>
                {this.props.showOnlineOnly ? (
                    <label className="flex items-center gap-2 rounded-sm border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-300 lg:mt-6">
                        <input
                            type="checkbox"
                            checked={this.props.onlineOnly === true}
                            onChange={(event) =>
                                this.props.onOnlineOnly
                                    ? this.props.onOnlineOnly(
                                          event.target.checked,
                                      )
                                    : null
                            }
                        />
                        <span>Online only</span>
                    </label>
                ) : null}
            </div>
        );
    }
}
