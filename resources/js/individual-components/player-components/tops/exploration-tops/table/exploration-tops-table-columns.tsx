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
                        key: "length_of_time_seconds",
                        label: "Length of Time",
                        align: "right",
                        type: "duration",
                    },
                    {
                        key: "xp_gained",
                        label: "XP",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "skill_xp_gained",
                        label: "Skill XP",
                        align: "right",
                        type: "number",
                    },
                ]}
            />
        );
    }
}
