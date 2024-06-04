var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React from "react";
import { formatNumber } from "../../lib/game/format-number";
var FactionNpcSection = (function (_super) {
    __extends(FactionNpcSection, _super);
    function FactionNpcSection(props) {
        return _super.call(this, props) || this;
    }
    FactionNpcSection.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement("h4", null, "Rewards (when fame levels up)"),
            React.createElement(
                "dl",
                { className: "my-2" },
                React.createElement("dt", null, "XP"),
                React.createElement(
                    "dd",
                    null,
                    formatNumber(
                        this.props.faction_loyalty_npc.current_level > 0
                            ? this.props.faction_loyalty_npc.current_level *
                                  1000
                            : 1000,
                    ),
                ),
                React.createElement("dt", null, "Gold"),
                React.createElement(
                    "dd",
                    null,
                    formatNumber(
                        this.props.faction_loyalty_npc.current_level > 0
                            ? this.props.faction_loyalty_npc.current_level *
                                  1000000
                            : 1000000,
                    ),
                ),
                React.createElement("dt", null, "Gold Dust"),
                React.createElement(
                    "dd",
                    null,
                    formatNumber(
                        this.props.faction_loyalty_npc.current_level > 0
                            ? this.props.faction_loyalty_npc.current_level *
                                  1000
                            : 1000,
                    ),
                ),
                React.createElement("dt", null, "Shards"),
                React.createElement(
                    "dd",
                    null,
                    formatNumber(
                        this.props.faction_loyalty_npc.current_level > 0
                            ? this.props.faction_loyalty_npc.current_level *
                                  1000
                            : 1000,
                    ),
                ),
                React.createElement("dt", null, "Item Reward"),
                React.createElement(
                    "dd",
                    null,
                    React.createElement(
                        "a",
                        {
                            href: "/information/random-enchants",
                            target: "_blank",
                        },
                        "Medium Unique Item",
                        " ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                ),
            ),
            React.createElement("h4", null, "Kingdom Item Defence Bonus"),
            React.createElement(
                "p",
                { className: "my-4" },
                "Slowly accumulates as you level this NPC's fame. Stacks with other NPCs on the same plane to a max of 95%.",
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Defence Bonus per level"),
                React.createElement(
                    "dd",
                    null,
                    (
                        this.props.faction_loyalty_npc
                            .kingdom_item_defence_bonus * 100
                    ).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Current Defence Bonus"),
                React.createElement(
                    "dd",
                    null,
                    (
                        this.props.faction_loyalty_npc
                            .current_kingdom_item_defence_bonus * 100
                    ).toFixed(0),
                    "%",
                ),
            ),
        );
    };
    return FactionNpcSection;
})(React.Component);
export default FactionNpcSection;
//# sourceMappingURL=faction-npc-section.js.map
