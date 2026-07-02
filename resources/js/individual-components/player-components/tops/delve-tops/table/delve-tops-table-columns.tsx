import React from "react";
import TopsLeaderboardTable from "../../shared/components/tops-leaderboard-table";
import TopsTableColumnsProps from "../../shared/types/tops-table-columns-props";

export default class DelveTopsTableColumns extends React.Component<TopsTableColumnsProps> {
    render() {
        return (
            <TopsLeaderboardTable
                rows={this.props.rows}
                fields={[
                    {
                        key: "strongest_enemy_increase",
                        label: "Strongest Enemy Increase",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "encounter_count",
                        label: "Encounters",
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
                        key: "average_pack_size",
                        label: "Average Pack Size",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "survived_count",
                        label: "Survived",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "latest_run_started_at",
                        label: "Latest Run",
                        align: "left",
                        type: "date",
                    },
                ]}
            />
        );
    }
}
