import React from "react";
import TopsDisplayField from "../../shared/types/tops-display-field";
import TopsLeaderboardDashboard from "../../shared/components/tops-leaderboard-dashboard";
import TopsPeriod from "../../shared/types/tops-period";
import KingdomLeaderboardProps from "../types/kingdom-leaderboard-props";

export default class KingdomLeaderboard extends React.Component<KingdomLeaderboardProps> {
    tableFields(): TopsDisplayField[] {
        return [
            {
                key: "kingdom_count",
                label: "Kingdoms",
                align: "right",
                type: "number",
            },
            {
                key: "total_value",
                label: "Total Value",
                align: "right",
                type: "number",
            },
            {
                key: "capital_count",
                label: "Capitals",
                align: "right",
                type: "number",
            },
            {
                key: "total_treasury",
                label: "Treasury",
                align: "right",
                type: "number",
            },
            {
                key: "total_gold_bars",
                label: "Gold Bars",
                align: "right",
                type: "number",
            },
            {
                key: "total_current_population",
                label: "Population",
                align: "right",
                type: "number",
            },
            {
                key: "total_resources",
                label: "Resources",
                align: "right",
                type: "number",
            },
            {
                key: "total_units",
                label: "Units",
                align: "right",
                type: "number",
            },
        ];
    }

    supportingMetrics(): TopsDisplayField[] {
        return [
            {
                key: "kingdom_count",
                label: "Kingdoms",
                align: "right",
                type: "number",
            },
            {
                key: "total_treasury",
                label: "Treasury",
                align: "right",
                type: "number",
            },
            {
                key: "total_gold_bars",
                label: "Gold Bars",
                align: "right",
                type: "number",
            },
            {
                key: "total_current_population",
                label: "Population",
                align: "right",
                type: "number",
            },
        ];
    }

    primaryMetric(): TopsDisplayField {
        const metric = this.props.leaderboard?.available_metrics.find(
            (availableMetric: TopsPeriod) =>
                availableMetric.key === this.props.metric,
        );

        return {
            key: this.props.metric,
            label: metric?.label ?? "Selected Metric",
            align: "right",
            type: "number",
        };
    }

    render() {
        return (
            <TopsLeaderboardDashboard
                title="Kingdom Leaderboard"
                description="The strongest kingdoms and rulers, ranked by kingdoms, treasury, gold bars, population, resources, and units."
                resetDescription="Leaderboards reset on the first day of each calendar month. Current Month is shown by default, previous monthly results stay available through archived snapshots, and All Time keeps lifetime totals."
                response={this.props.leaderboard}
                primaryMetric={this.primaryMetric()}
                supportingMetrics={this.supportingMetrics()}
                tableFields={this.tableFields()}
                period={this.props.period}
                metric={this.props.metric}
                search={this.props.search}
                loading={this.props.loading}
                errorMessage={this.props.errorMessage}
                minTableWidth="min-w-[1100px]"
                onPeriodChange={this.props.onPeriodChange}
                onMetricChange={this.props.onMetricChange}
                onSearch={this.props.onSearch}
            />
        );
    }
}
