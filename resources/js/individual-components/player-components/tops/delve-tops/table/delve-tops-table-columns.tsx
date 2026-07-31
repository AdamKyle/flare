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
                        label: "Total Enemy Strength %",
                        align: "right",
                        type: "percent",
                    },
                    {
                        key: "survived_duration_seconds",
                        label: "Survived Duration",
                        align: "right",
                        type: "duration",
                    },
                    {
                        key: "total_floors",
                        label: "Total Floors",
                        align: "right",
                        type: "number",
                    },
                ]}
            />
        );
    }
}
