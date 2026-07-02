import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsPodium from "./tops-podium";
import TopsMobileLeaderboardCards from "./tops-mobile-leaderboard-cards";
import TopsPeriodSelector from "./tops-period-selector";
import TopsMetricSelector from "./tops-metric-selector";
import TopsFilters from "./tops-filters";
import TopsEmptyState from "./tops-empty-state";
import TopsLoadingState from "./tops-loading-state";
import TopsErrorState from "./tops-error-state";
import TopsLeaderboardTable from "./tops-leaderboard-table";
import TopsLeaderboardDashboardProps from "../types/tops-leaderboard-dashboard-props";

export default class TopsLeaderboardDashboard extends React.Component<TopsLeaderboardDashboardProps> {
    renderLeaderboardContent() {
        if (this.props.loading) {
            return <TopsLoadingState />;
        }

        if (this.props.errorMessage !== null) {
            return <TopsErrorState message={this.props.errorMessage} />;
        }

        if (
            this.props.response === null ||
            this.props.response.rows.length === 0
        ) {
            return (
                <TopsEmptyState
                    message={
                        this.props.response?.empty_message ??
                        "No leaderboard data is available for this period yet."
                    }
                />
            );
        }

        return (
            <React.Fragment>
                <TopsMobileLeaderboardCards
                    rows={this.props.response.rows}
                    fields={this.props.tableFields}
                    primaryMetric={this.props.primaryMetric}
                    supportingMetrics={this.props.supportingMetrics}
                />
                <TopsLeaderboardTable
                    rows={this.props.response.rows}
                    fields={this.props.tableFields}
                    minTableWidth={this.props.minTableWidth}
                />
                {this.props.children}
            </React.Fragment>
        );
    }

    renderPodiumContent() {
        if (this.props.loading) {
            return <TopsLoadingState />;
        }

        if (this.props.errorMessage !== null) {
            return <TopsErrorState message={this.props.errorMessage} />;
        }

        if (
            this.props.response === null ||
            this.props.response.podium.length === 0
        ) {
            return (
                <TopsEmptyState message="No leaderboard data is available for this period yet." />
            );
        }

        return (
            <TopsPodium
                rows={this.props.response.podium}
                primaryMetric={this.props.primaryMetric}
                supportingMetrics={this.props.supportingMetrics}
            />
        );
    }

    render() {
        const availablePeriods = this.props.response?.available_periods ?? [];
        const availableMetrics = this.props.response?.available_metrics ?? [];

        return (
            <section className="space-y-6" aria-label={this.props.title}>
                <BasicCard additionalClasses="border border-gray-200 shadow-sm dark:border-gray-700">
                    <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Leaderboard Controls
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Change the period, metric, or search to refine the
                        current board.
                    </p>
                    <div className="mt-5 grid gap-4 lg:grid-cols-4">
                        <TopsPeriodSelector
                            periods={availablePeriods}
                            value={this.props.period}
                            onChange={this.props.onPeriodChange}
                        />
                        <TopsMetricSelector
                            metrics={availableMetrics}
                            value={this.props.metric}
                            onChange={this.props.onMetricChange}
                        />
                        <div className="lg:col-span-2">
                            <TopsFilters
                                search={this.props.search}
                                onlineOnly={this.props.onlineOnly}
                                showOnlineOnly={this.props.showOnlineOnly}
                                onSearch={this.props.onSearch}
                                onOnlineOnly={this.props.onOnlineOnly}
                            />
                        </div>
                    </div>
                </BasicCard>

                <BasicCard additionalClasses="border border-gray-200 shadow-sm dark:border-gray-700">
                    <div className="mb-2">
                        <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Top Three
                        </h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            The current podium for this leaderboard and period.
                        </p>
                    </div>
                    {this.renderPodiumContent()}
                </BasicCard>

                <BasicCard additionalClasses="border border-gray-200 shadow-sm dark:border-gray-700">
                    <div className="mb-5">
                        <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Leaderboard
                        </h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            All ranked characters for the selected period and
                            metric.
                        </p>
                    </div>
                    {this.renderLeaderboardContent()}
                </BasicCard>
            </section>
        );
    }
}
