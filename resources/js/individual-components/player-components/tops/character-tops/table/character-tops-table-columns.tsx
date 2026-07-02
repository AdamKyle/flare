import React from "react";
import TopsLeaderboardTable from "../../shared/components/tops-leaderboard-table";
import TopsTableColumnsProps from "../../shared/types/tops-table-columns-props";

export default class CharacterTopsTableColumns extends React.Component<TopsTableColumnsProps> {
    render() {
        return (
            <TopsLeaderboardTable
                rows={this.props.rows}
                fields={[
                    {
                        key: "times_reincarnated",
                        label: "Reincarnations",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "level",
                        label: "Level",
                        align: "right",
                        type: "number",
                    },
                    { key: "xp", label: "XP", align: "right", type: "number" },
                    { key: "race", label: "Race", align: "left", type: "text" },
                    {
                        key: "class",
                        label: "Class",
                        align: "left",
                        type: "text",
                    },
                    { key: "map", label: "Map", align: "left", type: "text" },
                    {
                        key: "gold",
                        label: "Gold",
                        align: "right",
                        type: "number",
                    },
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
                ]}
            />
        );
    }
}
