import React from "react";
import TopsDisplayField from "../../shared/types/tops-display-field";
import TopsLeaderboardDashboard from "../../shared/components/tops-leaderboard-dashboard";
import FactionLoyaltyLeaderboardProps from "../types/faction-loyalty-leaderboard-props";

export default class FactionLoyaltyLeaderboard extends React.Component<FactionLoyaltyLeaderboardProps> {
    tableFields(): TopsDisplayField[] {
        return [
            {
                key: "highest_faction_name",
                label: "Highest Level Faction",
                align: "left",
                type: "text",
            },
            {
                key: "total_faction_level",
                label: "Total Faction Level",
                align: "right",
                type: "number",
            },
            {
                key: "npcs_helped_count",
                label: "NPCs Helped",
                align: "right",
                type: "number",
            },
            {
                key: "total_npc_fame_level",
                label: "Total NPC Fame Level",
                align: "right",
                type: "number",
            },
        ];
    }

    supportingMetrics(): TopsDisplayField[] {
        return [
            {
                key: "highest_faction_name",
                label: "Faction",
                align: "left",
                type: "text",
            },
            {
                key: "total_faction_level",
                label: "Faction Lvls",
                align: "right",
                type: "number",
            },
            {
                key: "npcs_helped_count",
                label: "NPCs Helped",
                align: "right",
                type: "number",
            },
        ];
    }

    primaryMetric(): TopsDisplayField {
        return {
            key: "total_npc_fame_level",
            label: "NPC Fame Level",
            align: "right",
            type: "number",
        };
    }

    render() {
        return (
            <TopsLeaderboardDashboard
                title="Faction Loyalty Leaderboard"
                description="Ranked by Highest Level Faction, tie-broken by Total Faction Level, NPCs Helped, and Total NPC Fame Level."
                resetDescription="This leaderboard tracks live, cumulative character progress and does not reset each month. Current Month and All Time reflect the same up-to-date totals."
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
