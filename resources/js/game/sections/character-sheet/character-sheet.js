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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React, { Fragment } from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import CharacterTabs from "./components/character-tabs";
import CharacterSkillsTabs from "./components/character-skills-tabs";
import CharacterInventoryTabs from "./components/character-inventory-tabs";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import WarningAlert from "../../components/ui/alerts/simple-alerts/warning-alert";
import Select from "react-select";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../lib/ajax/ajax";
import ReincarnationCheckModal from "./components/modals/reincarnation-check-modal";
import AdditionalStatSection from "../../components/character-sheet/additional-stats-section/additional-stat-section";
import DangerButton from "../../components/ui/buttons/danger-button";
var CharacterSheet = (function (_super) {
    __extends(CharacterSheet, _super);
    function CharacterSheet(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_inventory_section: false,
            show_skills_section: false,
            show_top_section: false,
            show_additional_character_data: false,
            reincarnating: false,
            success_message: null,
            error_message: null,
            reincarnation_check: false,
        };
        return _this;
    }
    CharacterSheet.prototype.manageReincarnationCheck = function () {
        this.setState({
            reincarnation_check: !this.state.reincarnation_check,
        });
    };
    CharacterSheet.prototype.showSection = function () {
        if (typeof this.props.view_port === "undefined") {
            return true;
        }
        return this.props.view_port > 1600;
    };
    CharacterSheet.prototype.showCloseButton = function () {
        if (typeof this.props.view_port === "undefined") {
            return false;
        }
        return this.props.view_port < 1600;
    };
    CharacterSheet.prototype.showTopSection = function () {
        this.setState({
            show_top_section: !this.state.show_top_section,
        });
    };
    CharacterSheet.prototype.showSelectedSection = function (data) {
        switch (data.value) {
            case "inventory":
                return this.manageInventoryManagement();
            case "skills":
                return this.manageSkillsManagement();
            default:
                return;
        }
    };
    CharacterSheet.prototype.showAdditionalCharacterData = function () {
        this.setState({
            show_additional_character_data:
                !this.state.show_additional_character_data,
        });
    };
    CharacterSheet.prototype.manageInventoryManagement = function () {
        this.setState({
            show_inventory_section: !this.state.show_inventory_section,
        });
    };
    CharacterSheet.prototype.manageSkillsManagement = function () {
        this.setState({
            show_skills_section: !this.state.show_skills_section,
        });
    };
    CharacterSheet.prototype.reincarnateCharacter = function () {
        var _this = this;
        this.setState(
            {
                reincarnating: true,
                reincarnation_check: false,
            },
            function () {
                var _a;
                new Ajax()
                    .setRoute(
                        "character/reincarnate/" +
                            ((_a = _this.props.character) === null ||
                            _a === void 0
                                ? void 0
                                : _a.id),
                    )
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState({
                                reincarnating: false,
                                success_message: response.data.message,
                            });
                        },
                        function (error) {
                            _this.setState(
                                {
                                    reincarnating: false,
                                },
                                function () {
                                    if (typeof error.response !== "undefined") {
                                        var response = error.response;
                                        _this.setState({
                                            error_message:
                                                response.data.message,
                                        });
                                    }
                                },
                            );
                        },
                    );
            },
        );
    };
    CharacterSheet.prototype.render = function () {
        if (this.props.character === null) {
            return null;
        }
        if (this.state.show_additional_character_data) {
            return React.createElement(
                "div",
                null,
                React.createElement(
                    "div",
                    { className: "max-w-[25%] my-4" },
                    React.createElement(DangerButton, {
                        button_label: "Close",
                        on_click: this.showAdditionalCharacterData.bind(this),
                    }),
                ),
                React.createElement(AdditionalStatSection, {
                    character: this.props.character,
                }),
            );
        }
        return React.createElement(
            "div",
            null,
            this.props.character.is_dead
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "mb-4" },
                      React.createElement(
                          "p",
                          { className: "p-3" },
                          "Christ child! You are dead. Dead people cannot do a lot of things including: Manage inventory, Manage Skills - including passives, Manage Boons or even use items. And they cannot manage their kingdoms! How sad! Go resurrect child! (head to Game tab and click Revive).",
                      ),
                  )
                : null,
            this.props.character.is_automation_running
                ? React.createElement(
                      WarningAlert,
                      { additional_css: "mb-4" },
                      React.createElement(
                          "p",
                          { className: "p-3" },
                          "Child! You are busy with Automation. You cannot manage aspects of your inventory or skills such as whats training, passives or equipped items.",
                      ),
                      React.createElement(
                          "p",
                          { className: "p-3" },
                          "How ever, you can still manage the items you craft - such as sell, disenchant and destroy. You can also move items to sets, but not equip sets.",
                      ),
                      React.createElement(
                          "p",
                          { className: "p-3" },
                          "Please see",
                          " ",
                          React.createElement(
                              "a",
                              {
                                  href: "/information/automation",
                                  target: "_blank",
                              },
                              "Automation",
                              " ",
                              React.createElement("i", {
                                  className: "fas fa-external-link-alt",
                              }),
                          ),
                          " ",
                          "for more details.",
                      ),
                  )
                : null,
            React.createElement(
                "div",
                { className: "flex flex-col lg:flex-row w-full gap-2" },
                this.showSection() || this.state.show_top_section
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              BasicCard,
                              { additionalClasses: "overflow-y-auto lg:w-1/2" },
                              this.showCloseButton()
                                  ? React.createElement(
                                        "div",
                                        {
                                            className:
                                                "text-right cursor-pointer text-red-500 relative top-[10px]",
                                        },
                                        React.createElement(
                                            "button",
                                            {
                                                onClick:
                                                    this.showTopSection.bind(
                                                        this,
                                                    ),
                                            },
                                            React.createElement("i", {
                                                className:
                                                    "fas fa-minus-circle",
                                            }),
                                        ),
                                    )
                                  : null,
                              React.createElement(CharacterTabs, {
                                  character: this.props.character,
                                  finished_loading: this.props.finished_loading,
                                  view_port: this.props.view_port,
                                  manage_addition_data:
                                      this.showAdditionalCharacterData.bind(
                                          this,
                                      ),
                                  update_pledge_tab:
                                      this.props.update_pledge_tab,
                                  update_faction_action_tasks:
                                      this.props.update_faction_action_tasks,
                              }),
                          ),
                          React.createElement(
                              BasicCard,
                              {
                                  additionalClasses:
                                      "overflow-y-auto lg:w-1/2 md:max-h-[325px]",
                              },
                              React.createElement(
                                  "div",
                                  { className: "grid lg:grid-cols-2 gap-2" },
                                  React.createElement(
                                      "div",
                                      null,
                                      React.createElement(
                                          "dl",
                                          null,
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Gold:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.props.character.gold,
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Gold Dust:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.props.character.gold_dust,
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Shards:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.props.character.shards,
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Copper Coins:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.props.character.copper_coins,
                                          ),
                                      ),
                                      React.createElement(
                                          "div",
                                          { className: "mt-6 text-center" },
                                          React.createElement(PrimaryButton, {
                                              button_label:
                                                  "Reincarnate Character",
                                              on_click:
                                                  this.manageReincarnationCheck.bind(
                                                      this,
                                                  ),
                                          }),
                                          React.createElement(
                                              "p",
                                              { className: "text-sm my-2" },
                                              React.createElement(
                                                  "a",
                                                  {
                                                      href: "/information/reincarnation",
                                                      target: "_blank",
                                                  },
                                                  "What is Reincarnation?",
                                                  " ",
                                                  React.createElement("i", {
                                                      className:
                                                          "fas fa-external-link-alt",
                                                  }),
                                              ),
                                          ),
                                          this.state.reincarnating
                                              ? React.createElement(
                                                    LoadingProgressBar,
                                                    null,
                                                )
                                              : null,
                                          this.state.error_message !== null
                                              ? React.createElement(
                                                    "p",
                                                    {
                                                        className:
                                                            "text-red-500 dark:text-red-400 my-3",
                                                    },
                                                    this.state.error_message,
                                                )
                                              : null,
                                          this.state.success_message !== null
                                              ? React.createElement(
                                                    "p",
                                                    {
                                                        className:
                                                            "text-green-500 dark:text-green-400 my-3",
                                                    },
                                                    this.state.success_message,
                                                )
                                              : null,
                                      ),
                                  ),
                                  React.createElement("div", {
                                      className:
                                          "border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3",
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
                                              "Inventory Max:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.props.character
                                                  .inventory_max,
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Inventory Count:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.props.character
                                                  .inventory_count,
                                          ),
                                      ),
                                      React.createElement(
                                          "p",
                                          { className: "my-4" },
                                          "Inventory count consists of both Usable Items, Items in your inventory as well as your Gem Bag. Equipment, Quest items and Sets do not count towards inventory count.",
                                      ),
                                      React.createElement("div", {
                                          className:
                                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                                      }),
                                      React.createElement(
                                          "dl",
                                          null,
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Damage Stat:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.props.character.damage_stat,
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "To Hit:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.props.character.to_hit_stat,
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Class Bonus:",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              (
                                                  this.props.character
                                                      .extra_action_chance
                                                      .chance * 100
                                              ).toFixed(2),
                                              "%",
                                          ),
                                      ),
                                      React.createElement(
                                          "p",
                                          { className: "mt-4" },
                                          "Make sure you read up on your",
                                          " ",
                                          React.createElement(
                                              "a",
                                              {
                                                  href:
                                                      "/information/class/" +
                                                      this.props.character
                                                          .class_id,
                                                  target: "_blank",
                                              },
                                              "class",
                                              " ",
                                              React.createElement("i", {
                                                  className:
                                                      "fas fa-external-link-alt",
                                              }),
                                          ),
                                          " ",
                                          "for tips and tricks.",
                                      ),
                                  ),
                              ),
                          ),
                      )
                    : React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              BasicCard,
                              { additionalClasses: "overflow-y-auto lg:w-1/2" },
                              React.createElement(
                                  "span",
                                  { className: "relative top-[10px]" },
                                  React.createElement(
                                      "strong",
                                      null,
                                      "Character Details",
                                  ),
                              ),
                              React.createElement(
                                  "div",
                                  {
                                      className:
                                          "text-right cursor-pointer text-blue-500 relative top-[-12px]",
                                  },
                                  React.createElement(
                                      "button",
                                      {
                                          onClick:
                                              this.showTopSection.bind(this),
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-plus-circle",
                                      }),
                                  ),
                              ),
                          ),
                      ),
            ),
            React.createElement(
                "div",
                { className: "flex flex-col lg:flex-row gap-2 w-full mt-2" },
                this.showSection() || this.state.show_skills_section
                    ? React.createElement(
                          BasicCard,
                          {
                              additionalClasses:
                                  "overflow-y-auto lg:w-1/2 lg:h-fit",
                          },
                          this.showCloseButton()
                              ? React.createElement(
                                    "div",
                                    {
                                        className:
                                            "text-right cursor-pointer text-red-500 relative top-[10px]",
                                    },
                                    React.createElement(
                                        "button",
                                        {
                                            onClick:
                                                this.manageSkillsManagement.bind(
                                                    this,
                                                ),
                                        },
                                        React.createElement("i", {
                                            className: "fas fa-minus-circle",
                                        }),
                                    ),
                                )
                              : null,
                          React.createElement(CharacterSkillsTabs, {
                              character_id: this.props.character.id,
                              user_id: this.props.character.user_id,
                              is_dead: this.props.character.is_dead,
                              is_automation_running:
                                  this.props.character.is_automation_running,
                              finished_loading: this.props.finished_loading,
                          }),
                      )
                    : null,
                this.showSection() || this.state.show_inventory_section
                    ? React.createElement(
                          BasicCard,
                          {
                              additionalClasses:
                                  "overflow-y-auto lg:w-1/2 lg:h-fit",
                          },
                          this.showCloseButton()
                              ? React.createElement(
                                    "div",
                                    {
                                        className:
                                            "text-right cursor-pointer text-red-500 relative top-[10px]",
                                    },
                                    React.createElement(
                                        "button",
                                        {
                                            onClick:
                                                this.manageInventoryManagement.bind(
                                                    this,
                                                ),
                                        },
                                        React.createElement("i", {
                                            className: "fas fa-minus-circle",
                                        }),
                                    ),
                                )
                              : null,
                          React.createElement(CharacterInventoryTabs, {
                              character_id: this.props.character.id,
                              is_dead: this.props.character.is_dead,
                              user_id: this.props.character.user_id,
                              is_automation_running:
                                  this.props.character.is_automation_running,
                              finished_loading: this.props.finished_loading,
                              update_disable_tabs:
                                  this.props.update_disable_tabs,
                              view_port: this.props.view_port,
                          }),
                      )
                    : null,
                !this.showSection() &&
                    !this.state.show_inventory_section &&
                    !this.state.show_skills_section
                    ? React.createElement(Select, {
                          onChange: this.showSelectedSection.bind(this),
                          options: [
                              {
                                  label: "Inventory Management",
                                  value: "inventory",
                              },
                              {
                                  label: "Skill Management",
                                  value: "skills",
                              },
                          ],
                          menuPosition: "absolute",
                          menuPlacement: "bottom",
                          styles: {
                              menuPortal: function (base) {
                                  return __assign(__assign({}, base), {
                                      zIndex: 9999,
                                      color: "#000000",
                                  });
                              },
                          },
                          menuPortalTarget: document.body,
                          value: [{ label: "Please Select", value: "" }],
                      })
                    : null,
            ),
            this.state.reincarnation_check
                ? React.createElement(ReincarnationCheckModal, {
                      manage_modal: this.manageReincarnationCheck.bind(this),
                      handle_reincarnate: this.reincarnateCharacter.bind(this),
                  })
                : null,
        );
    };
    return CharacterSheet;
})(React.Component);
export default CharacterSheet;
//# sourceMappingURL=character-sheet.js.map
