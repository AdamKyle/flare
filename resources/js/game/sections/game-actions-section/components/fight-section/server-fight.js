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
import AttackButton from "../../../../components/ui/buttons/attack-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import HealthMeters from "../health-meters";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import clsx from "clsx";
var ServerFight = (function (_super) {
    __extends(ServerFight, _super);
    function ServerFight(props) {
        return _super.call(this, props) || this;
    }
    ServerFight.prototype.attackButtonDisabled = function () {
        return (
            this.props.monster_health <= 0 ||
            this.props.character_health <= 0 ||
            this.props.is_dead ||
            !this.props.can_attack ||
            this.props.monster_id === 0
        );
    };
    ServerFight.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            { className: "relative" },
            React.createElement(
                "div",
                {
                    className: clsx("mt-4 mb-4 text-xs text-center", {
                        hidden: this.attackButtonDisabled(),
                    }),
                },
                React.createElement(AttackButton, {
                    additional_css: "btn-attack",
                    icon_class: "ra ra-sword",
                    on_click: function () {
                        return _this.props.attack("attack");
                    },
                    disabled:
                        this.attackButtonDisabled() ||
                        this.props.preforming_action,
                }),
                React.createElement(AttackButton, {
                    additional_css: "btn-cast",
                    icon_class: "ra ra-burning-book",
                    on_click: function () {
                        return _this.props.attack("cast");
                    },
                    disabled:
                        this.attackButtonDisabled() ||
                        this.props.preforming_action,
                }),
                React.createElement(AttackButton, {
                    additional_css: "btn-cast-attack",
                    icon_class: "ra ra-lightning-sword",
                    on_click: function () {
                        return _this.props.attack("cast_and_attack");
                    },
                    disabled:
                        this.attackButtonDisabled() ||
                        this.props.preforming_action,
                }),
                React.createElement(AttackButton, {
                    additional_css: "btn-attack-cast",
                    icon_class: "ra ra-lightning-sword",
                    on_click: function () {
                        return _this.props.attack("attack_and_cast");
                    },
                    disabled:
                        this.attackButtonDisabled() ||
                        this.props.preforming_action,
                }),
                React.createElement(AttackButton, {
                    additional_css: "btn-defend",
                    icon_class: "ra ra-round-shield",
                    on_click: function () {
                        return _this.props.attack("defend");
                    },
                    disabled:
                        this.attackButtonDisabled() ||
                        this.props.preforming_action,
                }),
                React.createElement(
                    "a",
                    {
                        href: "/information/combat",
                        target: "_blank",
                        className: "ml-2",
                    },
                    "Help ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
            React.createElement(
                "div",
                {
                    className: clsx("mt-1 text-xs text-center ml-[-50px]", {
                        hidden: this.attackButtonDisabled(),
                    }),
                },
                React.createElement(
                    "span",
                    { className: "w-10 mr-4 ml-4" },
                    "Atk",
                ),
                React.createElement("span", { className: "w-10 ml-6" }, "Cast"),
                React.createElement(
                    "span",
                    { className: "w-10 ml-4" },
                    "Cast & Atk",
                ),
                React.createElement(
                    "span",
                    { className: "w-10 ml-2" },
                    "Atk & Cast",
                ),
                React.createElement(
                    "span",
                    { className: "w-10 ml-2" },
                    "Defend",
                ),
            ),
            this.props.monster_max_health > 0
                ? React.createElement(
                      "div",
                      {
                          className: clsx("mb-4 max-w-md m-auto", {
                              "mt-4": this.attackButtonDisabled(),
                          }),
                      },
                      React.createElement(HealthMeters, {
                          is_enemy: true,
                          name: this.props.monster_name,
                          current_health: Math.floor(this.props.monster_health),
                          max_health: Math.floor(this.props.monster_max_health),
                      }),
                      React.createElement(HealthMeters, {
                          is_enemy: false,
                          name: this.props.character_name,
                          current_health: Math.floor(
                              this.props.character_health,
                          ),
                          max_health: Math.floor(
                              this.props.character_max_health,
                          ),
                      }),
                  )
                : null,
            this.props.preforming_action
                ? React.createElement(
                      "div",
                      { className: "w-1/2 ml-auto mr-auto" },
                      React.createElement(LoadingProgressBar, null),
                  )
                : null,
            React.createElement(
                "div",
                { className: "italic text-center mb-4" },
                this.props.children,
            ),
            React.createElement(
                "div",
                { className: "text-center" },
                typeof this.props.manage_server_fight !== "undefined"
                    ? React.createElement(DangerButton, {
                          button_label: "Leave Fight",
                          on_click: this.props.manage_server_fight,
                          additional_css: "mr-4",
                          disabled: this.props.is_dead,
                      })
                    : null,
                this.props.is_dead
                    ? React.createElement(PrimaryButton, {
                          button_label: "Revive",
                          on_click: this.props.revive.bind(this),
                          disabled: !this.props.can_attack,
                      })
                    : null,
            ),
        );
    };
    return ServerFight;
})(React.Component);
export default ServerFight;
//# sourceMappingURL=server-fight.js.map
