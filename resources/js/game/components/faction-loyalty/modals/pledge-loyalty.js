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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
var PledgeLoyalty = (function (_super) {
    __extends(PledgeLoyalty, _super);
    function PledgeLoyalty(props) {
        return _super.call(this, props) || this;
    }
    PledgeLoyalty.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: true,
                handle_close: this.props.manage_modal,
                title: "Pledge Loyalty To: " + this.props.faction.map_name,
                secondary_actions: {
                    secondary_button_disabled: false,
                    secondary_button_label: "I pledge my allegiance",
                    handle_action: this.props.handle_pledge,
                },
            },
            React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    React.createElement(
                        "strong",
                        null,
                        "Would you like to pledge your loyalty to Surface?",
                    ),
                ),
                this.props.pledging
                    ? React.createElement(
                          "div",
                          { className: "my-4" },
                          React.createElement(LoadingProgressBar, null),
                      )
                    : null,
                React.createElement(
                    "div",
                    {
                        className:
                            "max-h-[450px] overflow-y-scroll md:max-h-auto md:overflow-y-visible",
                    },
                    React.createElement(
                        "p",
                        { className: "mb-4" },
                        "Pledging to a Faction allows a player to complete tasks to gain fame with the Npcs of that plane. As you complete tasks you will be rewarded with Currencies (Gold, Gold Dust and Shards), a",
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/random-enchants",
                                target: "_blank",
                            },
                            "Medium Unique Items",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        "and XP all (With exception of the item) equal to the current NPC Fame Level.",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "p",
                        { className: "mb-4" },
                        'Upon pledging, players will see a new tab on the Game tab named "Faction Loyalty". This tab will appear in your action section regardless of mobile or desktop. This tab will show you all the requirements for each NPC of that plane. There are two types of tasks: Bounties and Crafting. Crafting can be done any where and just requires you to click the "Craft for NPC" while crafting the item the NPC wants. Bounties are kill X creatures and must be done ',
                        React.createElement("strong", null, "manually"),
                        ".",
                    ),
                    React.createElement(
                        "p",
                        { className: "mb-4" },
                        "Each NPC will have a button called Assist. Players can assist only one NPC at a time with their tasks and must be assisting for their bounties and crafting to count. The total amount of tasks to do is the total amount of Fame needed to level the NPC. These tasks will switch each level.",
                    ),
                    React.createElement(
                        "p",
                        { className: "mb-4 italic" },
                        "It is suggested you",
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/automation",
                                target: "_blank",
                            },
                            "read more about Faction Loyalties",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        "for your own curiosity child.",
                    ),
                ),
            ),
        );
    };
    return PledgeLoyalty;
})(React.Component);
export default PledgeLoyalty;
//# sourceMappingURL=pledge-loyalty.js.map
