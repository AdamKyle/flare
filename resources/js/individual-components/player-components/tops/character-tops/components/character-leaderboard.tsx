import React from "react";
import TopsDisplayField from "../../shared/types/tops-display-field";
import TopsLeaderboardDashboard from "../../shared/components/tops-leaderboard-dashboard";
import CharacterLeaderboardProps from "../types/character-leaderboard-props";

export default class CharacterLeaderboard extends React.Component<CharacterLeaderboardProps> {
    tableFields(): TopsDisplayField[] {
        return [
            {
                key: "times_reincarnated",
                label: "Reincarnations",
                align: "right",
                type: "number",
            },
            { key: "level", label: "Level", align: "right", type: "number" },
            { key: "xp", label: "XP", align: "right", type: "number" },
            { key: "race", label: "Race", align: "left", type: "text" },
            { key: "class", label: "Class", align: "left", type: "text" },
            { key: "map", label: "Map", align: "left", type: "text" },
            { key: "gold", label: "Gold", align: "right", type: "number" },
            {
                key: "last_active_at",
                label: "Last Active",
                align: "left",
                type: "date",
            },
            {
                key: "online",
                label: "Online",
                align: "center",
                type: "boolean",
            },
        ];
    }

    supportingMetrics(): TopsDisplayField[] {
        return [
            {
                key: "times_reincarnated",
                label: "Reincarnations",
                align: "right",
                type: "number",
            },
            { key: "level", label: "Level", align: "right", type: "number" },
            { key: "xp", label: "XP", align: "right", type: "number" },
            { key: "gold", label: "Gold", align: "right", type: "number" },
        ];
    }

    render() {
        return (
            <TopsLeaderboardDashboard
                title="Character Progression"
                description="The highest progressing characters, ranked by reincarnations, level, XP, and gold."
                resetDescription="Leaderboards reset on the first day of each calendar month. Current Month is shown by default, previous monthly results stay available through archived snapshots, and All Time keeps lifetime totals."
                response={this.props.leaderboard}
                primaryMetric={{
                    key: "times_reincarnated",
                    label: "Progression",
                    align: "right",
                    type: "number",
                }}
                supportingMetrics={this.supportingMetrics()}
                tableFields={this.tableFields()}
                period={this.props.period}
                metric={this.props.metric}
                search={this.props.search}
                onlineOnly={this.props.onlineOnly}
                showOnlineOnly={true}
                loading={this.props.loading}
                errorMessage={this.props.errorMessage}
                onPeriodChange={this.props.onPeriodChange}
                onMetricChange={this.props.onMetricChange}
                onSearch={this.props.onSearch}
                onOnlineOnly={this.props.onOnlineOnly}
            />
        );
    }
}
