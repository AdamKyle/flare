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
import BasicCard from "../../ui/cards/basic-card";
import CoreCharacterStatsSection from "./sections/core-character-stats-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterReincarnationSection from "./sections/character-reincarnation-section";
import CharacterClassRanksSection from "./sections/character-class-ranks-section";
import CharacterElementalAtonementSection from "./sections/character-elemental-atonement-section";
var AdditionalStatSection = (function (_super) {
    __extends(AdditionalStatSection, _super);
    function AdditionalStatSection(props) {
        return _super.call(this, props) || this;
    }
    AdditionalStatSection.prototype.render = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement("h3", null, "Character Stats"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    React.createElement(CoreCharacterStatsSection, {
                        view_port: 0,
                        character: this.props.character,
                        is_open: true,
                        manage_modal: function () {},
                        title: "",
                        finished_loading: true,
                        when_tab_changes: function () {},
                    }),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden",
                }),
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement("h3", null, "Class Ranks"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    React.createElement(CharacterClassRanksSection, {
                        view_port: 0,
                        character: this.props.character,
                        is_open: true,
                        manage_modal: function () {},
                        title: "",
                        finished_loading: true,
                        when_tab_changes: function () {},
                    }),
                    React.createElement(
                        "p",
                        { className: "my-4" },
                        "Learn more about:",
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/class-ranks",
                                target: "_blank",
                            },
                            "Class Ranks",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                    ),
                ),
            ),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2 mt-4" },
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement(
                        "div",
                        { className: "grid md:grid-cols-2 gap-2" },
                        React.createElement(
                            "div",
                            null,
                            React.createElement("h3", null, "Resistances"),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                            }),
                            React.createElement(
                                "p",
                                { className: "my-3" },
                                "These resistances come from the rings you have equipped.",
                                " ",
                                React.createElement(
                                    "strong",
                                    null,
                                    "Spell Evasion",
                                ),
                                " will give you a chance at evading the enemies spells, while",
                                " ",
                                React.createElement(
                                    "strong",
                                    null,
                                    "Affix Damage Reduction",
                                ),
                                " will reduce incoming damage from enemy enchantments. Finally,",
                                " ",
                                React.createElement(
                                    "strong",
                                    null,
                                    "Enemy healing Reduction",
                                ),
                                ", will reduce the amount the enemy heals by.",
                            ),
                            React.createElement(
                                "div",
                                { className: "mt-3" },
                                React.createElement(ResistanceInfoSection, {
                                    view_port: 0,
                                    character: this.props.character,
                                    is_open: true,
                                    manage_modal: function () {},
                                    title: "",
                                    finished_loading: true,
                                }),
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden",
                        }),
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "h3",
                                null,
                                "Elemental Atonement",
                            ),
                            React.createElement("div", {
                                className:
                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                            }),
                            React.createElement(
                                CharacterElementalAtonementSection,
                                {
                                    character: this.props.character,
                                    is_open: true,
                                    manage_modal: function () {},
                                    title: "",
                                    finished_loading: true,
                                },
                            ),
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden",
                }),
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement("h3", null, "Resistances"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    React.createElement(
                        "p",
                        { className: "my-3" },
                        "This shows you: How many times you have reincarnated, the stats we apply to your character when they reincarnate back to level one. The damage stat modifier and the base damage stat modifier as well as the xp penalty for reincarnating.",
                    ),
                    React.createElement(
                        "p",
                        { className: "mb-6" },
                        React.createElement(
                            "a",
                            {
                                href: "/information/reincarnation",
                                target: "_blank",
                            },
                            "Reincarnation",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        'must be unlocked via completing a quest in Hell called: "Unlock the secrets of reincarnation" and require the use of an end game currency called:',
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/currencies",
                                target: "_blank",
                            },
                            "Copper Coins",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                    ),
                    React.createElement(CharacterReincarnationSection, {
                        view_port: 0,
                        character: this.props.character,
                        is_open: true,
                        manage_modal: function () {},
                        title: "",
                        finished_loading: true,
                    }),
                ),
            ),
        );
    };
    return AdditionalStatSection;
})(React.Component);
export default AdditionalStatSection;
//# sourceMappingURL=additional-stat-section.js.map
