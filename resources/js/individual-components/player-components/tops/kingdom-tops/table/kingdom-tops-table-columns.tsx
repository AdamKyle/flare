import React from "react";
import TopsLeaderboardTable from "../../shared/components/tops-leaderboard-table";
import TopsTableColumnsProps from "../../shared/types/tops-table-columns-props";

export default class KingdomTopsTableColumns extends React.Component<TopsTableColumnsProps> {
    render() {
        return (
            <TopsLeaderboardTable
                rows={this.props.rows}
                minTableWidth="min-w-[1100px]"
                fields={[
                    {
                        key: "total_value",
                        label: "Total Value",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "kingdom_count",
                        label: "Kingdoms",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "capital_count",
                        label: "Capitals",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "total_treasury",
                        label: "Treasury",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "total_gold_bars",
                        label: "Gold Bars",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "total_current_population",
                        label: "Population",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "total_resources",
                        label: "Resources",
                        align: "right",
                        type: "number",
                    },
                    {
                        key: "total_units",
                        label: "Units",
                        align: "right",
                        type: "number",
                    },
                ]}
            />
        );
    }
}
