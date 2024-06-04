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
import ComponentLoading from "../../../components/ui/loading/component-loading";
import QuestTree from "./components/quest-tree";
import DropDown from "../../../components/ui/drop-down/drop-down";
import { isEqual } from "lodash";
var Quests = (function (_super) {
    __extends(Quests, _super);
    function Quests(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            quests: [],
            raid_quests: [],
            completed_quests: _this.props.quest_details.completed_quests,
            current_plane: _this.props.quest_details.player_plane,
            is_winter_event: _this.props.quest_details.is_winter_event,
            loading: false,
        };
        return _this;
    }
    Quests.prototype.componentDidMount = function () {
        this.setState({
            quests: this.props.quest_details.quests,
            raid_quests: this.props.quest_details.raid_quests,
        });
    };
    Quests.prototype.componentDidUpdate = function () {
        if (
            !isEqual(
                this.props.quest_details.completed_quests,
                this.state.completed_quests,
            )
        ) {
            this.setState({
                completed_quests: this.props.quest_details.completed_quests,
            });
        }
    };
    Quests.prototype.setPlaneForQuests = function (plane) {
        this.setState({
            current_plane: plane,
        });
    };
    Quests.prototype.buildPlaneSelection = function () {
        var planes = [
            {
                name: "Surface",
                on_click: this.setPlaneForQuests.bind(this),
                icon_class: "ra ra-footprint",
            },
            {
                name: "Labyrinth",
                on_click: this.setPlaneForQuests.bind(this),
                icon_class: "ra ra-footprint",
            },
            {
                name: "Dungeons",
                on_click: this.setPlaneForQuests.bind(this),
                icon_class: "ra ra-footprint",
            },
            {
                name: "Shadow Plane",
                on_click: this.setPlaneForQuests.bind(this),
                icon_class: "ra ra-footprint",
            },
            {
                name: "Hell",
                on_click: this.setPlaneForQuests.bind(this),
                icon_class: "ra ra-footprint",
            },
            {
                name: "Twisted Memories",
                on_click: this.setPlaneForQuests.bind(this),
                icon_class: "ra ra-footprint",
            },
        ];
        if (this.props.quest_details.is_winter_event) {
            planes.push({
                name: "The Ice Plane",
                on_click: this.setPlaneForQuests.bind(this),
                icon_class: "ra ra-footprint",
            });
        }
        if (this.props.quest_details.is_delusional_memories) {
            planes.push({
                name: "Delusional Memories",
                on_click: this.setPlaneForQuests.bind(this),
                icon_class: "ra ra-footprint",
            });
        }
        return planes;
    };
    Quests.prototype.render = function () {
        if (this.state.quests.length === 0) {
            return React.createElement(ComponentLoading, null);
        }
        return React.createElement(
            Fragment,
            null,
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "h-24 mt-10 relative" },
                      React.createElement(ComponentLoading, null),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "div",
                          { className: "flex items-center" },
                          React.createElement(
                              "div",
                              null,
                              React.createElement(DropDown, {
                                  menu_items: this.buildPlaneSelection(),
                                  button_title: "Planes",
                              }),
                          ),
                          React.createElement(
                              "div",
                              null,
                              React.createElement(
                                  "a",
                                  {
                                      href: "/information/quests",
                                      target: "_blank",
                                      className: "ml-2",
                                  },
                                  "Quests help",
                                  " ",
                                  React.createElement("i", {
                                      className: "fas fa-external-link-alt",
                                  }),
                              ),
                          ),
                      ),
                      React.createElement("div", {
                          className:
                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                      }),
                      React.createElement(
                          "div",
                          {
                              className:
                                  "overflow-x-auto overflow-y-hidden max-w-[300px] sm:max-w-[600px] md:max-w-[100%]",
                          },
                          React.createElement(QuestTree, {
                              quests: this.state.quests,
                              raid_quests: this.state.raid_quests,
                              completed_quests: this.state.completed_quests,
                              character_id: this.props.character_id,
                              plane: this.state.current_plane,
                              update_quests: this.props.update_quests,
                          }),
                      ),
                  ),
        );
    };
    return Quests;
})(React.Component);
export default Quests;
//# sourceMappingURL=quests.js.map
