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
import React, { Fragment } from "react";
import { formatNumber } from "../../../../lib/game/format-number";
import SpecialLocationHelpModal from "./special-location-help-modal";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
var LocationDetails = (function (_super) {
    __extends(LocationDetails, _super);
    function LocationDetails(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            open_help_dialogue: false,
        };
        return _this;
    }
    LocationDetails.prototype.manageHelpDialogue = function () {
        this.setState({
            open_help_dialogue: !this.state.open_help_dialogue,
        });
    };
    LocationDetails.prototype.isSpecialLocation = function () {
        return this.props.location.increase_enemy_percentage_by !== null;
    };
    LocationDetails.prototype.renderSpecialType = function () {
        if (this.props.location.type_name === "Gold Mines") {
            return React.createElement(
                Fragment,
                null,
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement(
                    "h5",
                    { className: "text-orange-500 dark:text-orange-400" },
                    "Gold Mines!",
                ),
                React.createElement(
                    WarningAlert,
                    { additional_css: "my-4 font-bold" },
                    "Exploration cannot be used here if you want the below rewards. You must manually fight.",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "Welcome to the Gold Mines, a special mid game location to help players start farming currencies for end game gear while they continue their questing to unlock more of the game and work further towards the true power of their character! Come now child, death awaits!",
                ),
                React.createElement(
                    "ul",
                    { className: "list-disc" },
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-10,000 Gold from fighting monsters. This can be increased to 20,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-500 Gold Dust from fighting monsters. This can be increased to 1,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-500 Shards from fighting monsters. This can be increased to 1,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "There is a 1/1,000,000 (+15% Looting) chance to get a random",
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/random-enchants",
                                target: "_blank",
                            },
                            "Medium Unique",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        "from Monsters half way down the list of more. This can be reduced to 1/500,000 (+30% Looting) chance if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        'There is a 1/1,000,000 chance to trigger an event while fighting here to reduce the chances and increase the currencies (the above "if an event is triggered") for 1 hour at this location only.',
                    ),
                ),
            );
        }
        if (this.props.location.type_name === "The Old Church") {
            return React.createElement(
                Fragment,
                null,
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement(
                    "h5",
                    { className: "text-orange-500 dark:text-orange-400" },
                    "The Old Church!",
                ),
                React.createElement(
                    WarningAlert,
                    { additional_css: "my-4 font-bold" },
                    "Exploration cannot be used here if you want the below rewards. You must manually fight.",
                ),
                React.createElement(
                    InfoAlert,
                    { additional_css: "my-4 font-bold" },
                    "The below only applies to those who poses the Christmas Tree Light Bulb Quest item from completing a quest chain that starts with: Thousands of Years Ago ... and ends with: The doors to The Old Church.",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "Welcome to the The Old Church, a special mid game location to help players start farming currencies for end game gear while they continue their questing to unlock more of the game and work further towards the true power of their character! Come now child, death awaits!",
                ),
                React.createElement(
                    "ul",
                    { className: "list-disc" },
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-20,000 Gold from fighting monsters. This can be increased to 40,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-1000 Gold Dust from fighting monsters. This can be increased to 2,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-1000 Shards from fighting monsters. This can be increased to 2,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "There is a 1/1,000,000 (+15% Looting) chance to get a random",
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/random-enchants",
                                target: "_blank",
                            },
                            "Medium Unique",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        "from Monsters half way down the list of more. This can be reduced to 1/500,000 (+30% Looting) chance if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        'There is a 1/1,000,000 chance to trigger an event while fighting here to reduce the chances and increase the currencies (the above "if an event is triggered") for 1 hour at this location only.',
                    ),
                ),
            );
        }
        if (this.props.location.type_name === "Purgatory Dungeons") {
            return React.createElement(
                Fragment,
                null,
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement(
                    "h5",
                    { className: "text-orange-500 dark:text-orange-400" },
                    "Purgatory Dungeons!",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "You have entered into the Purgatory Dungeons. You",
                    " ",
                    React.createElement("strong", null, "can explore here"),
                    ". This is the only place known to drop",
                    " ",
                    React.createElement(
                        "a",
                        {
                            href: "/information/mythical-items",
                            target: "_blank",
                        },
                        "Mythic Items",
                        " ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                    ", aside from Celestial Kings who spawn after",
                    " ",
                    React.createElement(
                        "a",
                        {
                            href: "/information/monthly-pvp-event",
                            target: "_blank",
                        },
                        "Monthly PVP",
                        " ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                ),
            );
        }
        if (this.props.location.type_name === "Purgatory Smiths House") {
            return React.createElement(
                Fragment,
                null,
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement(
                    "h5",
                    { className: "text-orange-500 dark:text-orange-400" },
                    "Purgatory Smith House!",
                ),
                React.createElement(
                    WarningAlert,
                    { additional_css: "my-4 font-bold" },
                    "Exploration cannot be used here if you want the below rewards. You must use manually fight.",
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "In this location, a few things will happen for those who have access:",
                ),
                React.createElement(
                    "ul",
                    { className: "list-disc" },
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-1000 Gold Dust from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-1000 Shards from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "Characters can get 1-1000 Copper Coins",
                        React.createElement("sup", null, "*"),
                        " ",
                        "from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "There is a 1/1,000,000 chance to get a Purgatory Chain",
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/random-enchants",
                                target: "_blank",
                            },
                            "Legendary Unique",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        "from Monsters half way down the list of more. This can be reduced to 1/500,000 chance if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        "There is a 1/10,000,000 chance to get a Purgatory Chain",
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/mythical-items",
                                target: "_blank",
                            },
                            "Mythic Items",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        "from the last monster in the list. This can be reduced to 1/5,000,000 chance if an event is triggered at this area.",
                    ),
                    React.createElement(
                        "li",
                        { className: "ml-4" },
                        'There is a 1/1,000,000 chance to trigger an event while fighting here to reduce the chances and increase the currencies (the above "if an event is triggered") for 1 hour at this location only.',
                    ),
                ),
                React.createElement(
                    "p",
                    { className: "mt-4 mb-4 italic" },
                    React.createElement("sup", null, "*"),
                    " Provided characters have the required quest item to obtain copper coins.",
                ),
            );
        }
        return null;
    };
    LocationDetails.prototype.renderRaidDetails = function () {
        if (this.props.location.is_corrupted) {
            return React.createElement(
                Fragment,
                null,
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement(
                    "h5",
                    { className: "text-orange-500 dark:text-orange-400" },
                    "Corrupted",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "This location has been corrupted by evil forces! There happens to be a raid going on here, the monsters of this location are exceptionally hard, how ever quest items that you would get for visiting the place, if applicable, will still drop.",
                ),
                this.props.location.has_raid_boss
                    ? React.createElement(
                          "p",
                          { className: "my-4 font-bold" },
                          "The raid boss lives here! He will be the first monster in the list!",
                      )
                    : null,
                React.createElement(
                    "p",
                    { className: "my-4 italic text-sm" },
                    "It is recommended that players have top tier gear, have reincarnated (at max level) at least twice and have gear with sockets and Gems attached. All players are welcome to participate in the raid, regardless of gear or level, but the more prepared the better chances you have.",
                ),
            );
        }
    };
    LocationDetails.prototype.renderWeeklyFightLocationDetails = function () {
        var validLocationNames = ["Alchemy Church"];
        if (this.props.location.type_name === null) {
            return;
        }
        if (validLocationNames.includes(this.props.location.type_name)) {
            return React.createElement(
                Fragment,
                null,
                React.createElement(
                    "h5",
                    { className: "text-orange-500 dark:text-orange-400" },
                    "Corrupted Alchemy Church",
                ),
                React.createElement(
                    WarningAlert,
                    { additional_css: "my-4 font-bold" },
                    "Exploration cannot be used here if you want the below rewards. You must use manually fight.",
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "Players who fight here have a smaller selection of monsters to choose from. These are harder creatures. Each monster in the list is restricted to once per week.",
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "Players who kill a monster here have a 1% + (max) 15% of their looting skill - 0.02% for each character death, to get a Cosmic item. This item is similar to a Mythic, in the sense that only one can be equipped - but also much more powerful then Mythics.",
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "These types of weekly fights reset every Sunday at 3 AM America/Edmonton time.",
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3",
                }),
            );
        }
    };
    LocationDetails.prototype.renderInfoForSpecialMaps = function () {
        if (
            this.props.location.game_map_name === "Delusional Memories" ||
            this.props.location.game_map_name === "The Ice Plane"
        ) {
            return React.createElement(
                InfoAlert,
                { additional_css: "my-2" },
                "If you do not have access to Purgatory, than the enemy strength boost of this location does not apply to you. You encounter regular creatures. The rest of the locations effects, such as drop rates and manual fighting still apply.",
            );
        }
    };
    LocationDetails.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "p",
                { className: "my-3" },
                this.props.location.description,
            ),
            this.renderRaidDetails(),
            this.isSpecialLocation()
                ? React.createElement(
                      "div",
                      {
                          className:
                              "max-h-[350px] lg:max-h-auto overflow-y-auto",
                      },
                      React.createElement("div", {
                          className:
                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                      }),
                      this.renderInfoForSpecialMaps(),
                      React.createElement(
                          "div",
                          { className: "flex items-center mb-4" },
                          React.createElement(
                              "h4",
                              null,
                              "Special Location Details",
                          ),
                          React.createElement(
                              "div",
                              null,
                              React.createElement(
                                  "button",
                                  {
                                      type: "button",
                                      onClick:
                                          this.manageHelpDialogue.bind(this),
                                      className:
                                          "text-blue-500 dark:text-blue-300 ml-2",
                                  },
                                  React.createElement("i", {
                                      className: "fas fa-info-circle",
                                  }),
                                  " ",
                                  "Help",
                              ),
                          ),
                      ),
                      React.createElement(
                          "p",
                          { className: "mb-4" },
                          "Places like this can increase the enemies stats and resistances as well as skills. It is essential that players craft appropriate resistance and stat reduction gear to survive harder creatures here.",
                      ),
                      React.createElement(
                          "dl",
                          { className: "mb-4" },
                          React.createElement(
                              "dt",
                              null,
                              "Increase Core Stats By:",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              formatNumber(
                                  this.props.location.increases_enemy_stats_by,
                              ),
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Increase Percentage Based Values By:",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              this.props.location
                                  .increase_enemy_percentage_by !== null
                                  ? (
                                        this.props.location
                                            .increase_enemy_percentage_by * 100
                                    ).toFixed(0)
                                  : 0,
                              "%",
                          ),
                          React.createElement("dt", null, "Drop Chance"),
                          React.createElement(
                              "dd",
                              null,
                              "1/100 chance for quest items with a cap of 45% of your looting skill. (If your looting skill bonus is 45% or higher we only use 45%)",
                          ),
                      ),
                      this.props.location.type_name !== null
                          ? this.renderSpecialType()
                          : null,
                  )
                : this.renderWeeklyFightLocationDetails(),
            this.props.location.quest_reward_item_id !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "dl",
                          { className: "mb-4" },
                          React.createElement(
                              "dt",
                              null,
                              "Quest Item (Gained on visiting)",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              React.createElement(
                                  "a",
                                  {
                                      href:
                                          "/information/item/" +
                                          this.props.location
                                              .quest_reward_item_id,
                                      target: "_blank",
                                  },
                                  this.props.location.quest_reward_item
                                      .affix_name,
                                  " ",
                                  React.createElement("i", {
                                      className: "fas fa-external-link-alt",
                                  }),
                              ),
                          ),
                      ),
                  )
                : null,
            this.props.location.required_quest_item_id !== null
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          WarningAlert,
                          null,
                          "You cannot simply enter this location with out having the item below.",
                      ),
                      React.createElement(
                          "dl",
                          { className: "my-4" },
                          React.createElement(
                              "dt",
                              null,
                              "Quest Item Required To Enter",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              React.createElement(
                                  "a",
                                  {
                                      href:
                                          "/information/item/" +
                                          this.props.location
                                              .required_quest_item_id,
                                      target: "_blank",
                                  },
                                  this.props.location.required_quest_item_name,
                                  " ",
                                  React.createElement("i", {
                                      className: "fas fa-external-link-alt",
                                  }),
                              ),
                          ),
                      ),
                  )
                : null,
            this.state.open_help_dialogue
                ? React.createElement(SpecialLocationHelpModal, {
                      manage_modal: this.manageHelpDialogue.bind(this),
                  })
                : null,
        );
    };
    return LocationDetails;
})(React.Component);
export default LocationDetails;
//# sourceMappingURL=location-details.js.map
