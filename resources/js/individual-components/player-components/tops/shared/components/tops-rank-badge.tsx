import React from "react";
import { rankBadgeClasses } from "../helpers/tops-rank-styles";
import TopsRankBadgeProps from "../types/tops-rank-badge-props";

export default class TopsRankBadge extends React.Component<TopsRankBadgeProps> {
    render() {
        return (
            <span
                className={
                    "inline-flex items-center justify-center rounded-full border px-3 py-1 text-xs font-bold tabular-nums " +
                    rankBadgeClasses(this.props.rank)
                }
            >
                Rank {this.props.rank}
            </span>
        );
    }
}
