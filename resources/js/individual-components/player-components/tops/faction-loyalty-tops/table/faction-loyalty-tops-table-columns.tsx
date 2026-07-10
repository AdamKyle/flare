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
                ]}
            />
        );
    }
}
