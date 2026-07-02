import React from "react";
import TopsLeaderboardTable from "../../shared/components/tops-leaderboard-table";
import TopsTableColumnsProps from "../../shared/types/tops-table-columns-props";

export default class FactionLoyaltyTopsTableColumns extends React.Component<TopsTableColumnsProps> {
    render() {
        return (
            <TopsLeaderboardTable
                rows={this.props.rows}
                fields={[
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
                ]}
            />
        );
    }
}
