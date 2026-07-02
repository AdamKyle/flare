import React from "react";
import TopsDisplayField from "../../shared/types/tops-display-field";
import TopsLeaderboardDashboard from "../../shared/components/tops-leaderboard-dashboard";
import TopsPeriod from "../../shared/types/tops-period";
import FactionLoyaltyLeaderboardProps from "../types/faction-loyalty-leaderboard-props";

export default class FactionLoyaltyLeaderboard extends React.Component<FactionLoyaltyLeaderboardProps> {
    tableFields(): TopsDisplayField[] {
        return [
            {
                key: "highest_faction_level",
                label: "Highest Faction Level",
                align: "right",
                type: "number",
            },
            {
                key: "highest_faction_points",
                label: "Highest Faction Points",
                align: "right",
                type: "number",
            },
            {
                key: "maxed_faction_count",
                label: "Maxed Factions",
                align: "right",
                type: "number",
            },
            {
                key: "highest_npc_loyalty_level",
                label: "NPC Loyalty",
                align: "right",
                type: "number",
            },
            {
                key: "automation_run_count",
                label: "Automation Runs",
                align: "right",
                type: "number",
            },
            {
                key: "latest_action",
                label: "Latest Action",
                align: "left",
                type: "text",
            },
        ];
    }

    supportingMetrics(): TopsDisplayField[] {
        return [
            {
                key: "highest_faction_level",
                label: "Highest Faction Level",
                align: "right",
                type: "number",
            },
            {
                key: "highest_faction_points",
                label: "Highest Faction Points",
                align: "right",
                type: "number",
            },
            {
                key: "maxed_faction_count",
                label: "Maxed Factions",
                align: "right",
                type: "number",
            },
            {
                key: "highest_npc_loyalty_level",
                label: "NPC Loyalty",
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
            label: metric?.label ?? "Highest Faction Level",
            align: "right",
            type: "number",
        };
    }

    render() {
        return (
            <TopsLeaderboardDashboard
                title="Faction Loyalty Leaderboard"
                description="The strongest faction progression, ranked by fame, maxed factions, NPC loyalty, and automation activity."
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
                onPeriodChange={this.props.onPeriodChange}
                onMetricChange={this.props.onMetricChange}
                onSearch={this.props.onSearch}
            />
        );
    }
}
