import React from "react";
import TopsLeaderboardTable from "../../shared/components/tops-leaderboard-table";
import TopsTableColumnsProps from "../../shared/types/tops-table-columns-props";

export default class ExplorationTopsTableColumns extends React.Component<TopsTableColumnsProps> {
    render() {
        return (
            <TopsLeaderboardTable
                rows={this.props.rows}
                fields={[
                    {
                        key: "kills",
                        label: "Kills",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "fights",
                        label: "Fights",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "xp_gained",
                        label: "XP Gained",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "skill_xp_gained",
                        label: "Skill XP",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "run_count",
                        label: "Runs",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "latest_started_at",
                        label: "Latest Started",
                        align: "left",
                        type: "date",
                    },
                ]}
            />
        );
    }
}
