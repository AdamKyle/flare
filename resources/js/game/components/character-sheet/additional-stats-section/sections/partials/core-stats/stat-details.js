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
import { formatNumber } from "../../../../../../lib/game/format-number";
import PrimaryLinkButton from "../../../../../ui/buttons/primary-link-button";
import StatBreakDown from "../stat-break-down/stat-break-down";
import HealthBreakDown from "../stat-break-down/health-break-down";
import ArmourClassBreakDown from "../stat-break-down/armour-class-break-down";
import DamageBreakDown from "../stat-break-down/damage-break-down";
var StatDetails = (function (_super) {
    __extends(StatDetails, _super);
    function StatDetails(props) {
        var _this = _super.call(this, props) || this;
        _this.STAT_MODIFIERS = [
            "str",
            "dex",
            "int",
            "chr",
            "focus",
            "agi",
            "dur",
        ];
        _this.SPECIFIC = [
            "weapon_damage",
            "ring_damage",
            "ac",
            "health",
            "spell_damage",
            "heal_for",
        ];
        _this.state = {
            show_detailed_section: false,
            details_type: null,
            show_voided: false,
        };
        return _this;
    }
    StatDetails.prototype.showTypeDetails = function (type, voidedVersion) {
        this.setState({
            show_detailed_section: true,
            details_type: type,
            show_voided: voidedVersion,
        });
    };
    StatDetails.prototype.closeTypeDetails = function () {
        this.setState({
            show_detailed_section: false,
            details_type: null,
            show_voided: false,
        });
    };
    StatDetails.prototype.render = function () {
        var _this = this;
        if (
            this.state.show_detailed_section &&
            this.state.details_type !== null
        ) {
            if (this.STAT_MODIFIERS.includes(this.state.details_type)) {
                return React.createElement(StatBreakDown, {
                    close_section: this.closeTypeDetails.bind(this),
                    type: this.state.details_type,
                    character_id: this.props.character.id,
                });
            }
            if (this.SPECIFIC.includes(this.state.details_type)) {
                switch (this.state.details_type) {
                    case "health":
                        return React.createElement(HealthBreakDown, {
                            close_section: this.closeTypeDetails.bind(this),
                            type: this.state.details_type,
                            character_id: this.props.character.id,
                            is_voided: this.state.show_voided,
                        });
                    case "ac":
                        return React.createElement(ArmourClassBreakDown, {
                            close_section: this.closeTypeDetails.bind(this),
                            type: this.state.details_type,
                            character_id: this.props.character.id,
                            is_voided: this.state.show_voided,
                        });
                    case "weapon_damage":
                    case "spell_damage":
                    case "heal_for":
                    case "ring_damage":
                        return React.createElement(DamageBreakDown, {
                            close_section: this.closeTypeDetails.bind(this),
                            type: this.state.details_type,
                            character_id: this.props.character.id,
                            is_voided: this.state.show_voided,
                        });
                    default:
                        return null;
                }
            }
        }
        return React.createElement(
            "div",
            {
                className:
                    "max-h-[350px] md:max-h-full overflow-y-scroll md:overflow-y-visible",
            },
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Raw Str"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.str),
                        ),
                        React.createElement("dt", null, "Raw Dex"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.dex),
                        ),
                        React.createElement("dt", null, "Raw Agi"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.agi),
                        ),
                        React.createElement("dt", null, "Raw Chr"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.chr),
                        ),
                        React.createElement("dt", null, "Raw Dur"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.dur),
                        ),
                        React.createElement("dt", null, "Raw Int"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.int),
                        ),
                        React.createElement("dt", null, "Raw Focus"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.focus),
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Modded Str",
                                on_click: function () {
                                    _this.showTypeDetails("str", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.str_modded),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Modded Dex",
                                on_click: function () {
                                    _this.showTypeDetails("dex", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.dex_modded),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Modded Agi",
                                on_click: function () {
                                    _this.showTypeDetails("agi", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.agi_modded),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Modded Chr",
                                on_click: function () {
                                    _this.showTypeDetails("chr", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.chr_modded),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Modded Dur",
                                on_click: function () {
                                    _this.showTypeDetails("dur", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.dur_modded),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Modded Int",
                                on_click: function () {
                                    _this.showTypeDetails("int", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.int_modded),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Modded Focus",
                                on_click: function () {
                                    _this.showTypeDetails("focus", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.focus_modded),
                        ),
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-3 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Health",
                                on_click: function () {
                                    _this.showTypeDetails("health", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.health),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Voided Health",
                                on_click: function () {
                                    _this.showTypeDetails("health", true);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.voided_health),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Ac",
                                on_click: function () {
                                    _this.showTypeDetails("ac", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.ac),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Voided Ac",
                                on_click: function () {
                                    _this.showTypeDetails("ac", true);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.voided_ac),
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Weapon Damage",
                                on_click: function () {
                                    _this.showTypeDetails(
                                        "weapon_damage",
                                        false,
                                    );
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.weapon_attack),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Ring Damage",
                                on_click: function () {
                                    _this.showTypeDetails("ring_damage", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.ring_damage),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Spell Damage",
                                on_click: function () {
                                    _this.showTypeDetails(
                                        "spell_damage",
                                        false,
                                    );
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.spell_damage),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Healing Amount",
                                on_click: function () {
                                    _this.showTypeDetails("heal_for", false);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(
                                this.props.stat_details.healing_amount,
                            ),
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Voided Weapon Damage",
                                on_click: function () {
                                    _this.showTypeDetails(
                                        "weapon_damage",
                                        true,
                                    );
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(
                                this.props.stat_details.voided_weapon_attack,
                            ),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Voided Ring Damage",
                                on_click: function () {
                                    _this.showTypeDetails("ring_damage", true);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.stat_details.ring_damage),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Voided Spell Damage",
                                on_click: function () {
                                    _this.showTypeDetails("spell_damage", true);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(
                                this.props.stat_details.voided_spell_damage,
                            ),
                        ),
                        React.createElement(
                            "dt",
                            null,
                            React.createElement(PrimaryLinkButton, {
                                button_label: "Voided Healing Amount",
                                on_click: function () {
                                    _this.showTypeDetails("heal_for", true);
                                },
                            }),
                        ),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(
                                this.props.stat_details.voided_healing_amount,
                            ),
                        ),
                    ),
                ),
            ),
            React.createElement(
                "p",
                { className: "mt-4 mb-2" },
                React.createElement("sup", null, "*"),
                " For more information please see",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/voidance", target: "_blank" },
                    "Voidance Help",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return StatDetails;
})(React.Component);
export default StatDetails;
//# sourceMappingURL=stat-details.js.map
