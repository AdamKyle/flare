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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Select from "react-select";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
var TraverseModal = (function (_super) {
    __extends(TraverseModal, _super);
    function TraverseModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            game_maps: [],
            is_traversing: false,
            error_message: null,
            traverse_is_same_map: true,
            map: 0,
        };
        return _this;
    }
    TraverseModal.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax().setRoute("map/traverse-maps").doAjaxCall(
            "get",
            function (result) {
                _this.setState(
                    {
                        game_maps: result.data,
                        loading: false,
                    },
                    function () {
                        _this.disableTraverseForSameMap();
                    },
                );
            },
            function (error) {
                if (typeof error.response !== "undefined") {
                    var response = error.response;
                    _this.setState({
                        loading: false,
                        error_message: response.data.message,
                    });
                }
            },
        );
    };
    TraverseModal.prototype.setMap = function (data) {
        var _this = this;
        if (data.value <= 0) {
            return;
        }
        this.setState(
            {
                map: data.value,
            },
            function () {
                _this.disableTraverseForSameMap();
            },
        );
    };
    TraverseModal.prototype.buildTraverseOptions = function () {
        if (this.state.game_maps.length > 0) {
            return this.state.game_maps.map(function (game_map) {
                return { label: game_map.name, value: game_map.id };
            });
        }
        return [];
    };
    TraverseModal.prototype.getDefaultValue = function () {
        var _this = this;
        var playerMap = this.state.game_maps.filter(function (map) {
            return map.id === _this.props.map_id;
        })[0];
        if (!playerMap) {
            return { label: "Please select", value: 0 };
        }
        if (this.state.map === 0) {
            return { label: playerMap.name, value: playerMap.id };
        }
        var map = this.state.game_maps.filter(function (map) {
            return map.id === _this.state.map;
        })[0];
        return {
            label: map.name,
            value: map.id,
        };
    };
    TraverseModal.prototype.disableTraverseForSameMap = function () {
        if (this.state.map === 0) {
            return;
        }
        this.setState({
            traverse_is_same_map: this.state.map === this.props.map_id,
        });
    };
    TraverseModal.prototype.traverse = function () {
        var _this = this;
        this.setState({
            is_traversing: true,
        });
        new Ajax()
            .setRoute("map/traverse/" + this.props.character_id)
            .setParameters({
                map_id: this.state.map,
            })
            .doAjaxCall(
                "post",
                function (result) {
                    _this.setState({
                        is_traversing: false,
                    });
                    _this.props.handle_close();
                },
                function (error) {
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            is_traversing: false,
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    TraverseModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Traverse",
                primary_button_disabled: this.state.is_traversing,
                secondary_actions: {
                    handle_action: this.traverse.bind(this),
                    secondary_button_disabled:
                        this.state.is_traversing ||
                        this.state.loading ||
                        this.state.traverse_is_same_map,
                    secondary_button_label: "Traverse",
                },
            },
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "p-10" },
                      React.createElement(ComponentLoading, null),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "p",
                          { className: "mb-4" },
                          "Welcome to traverse. Every plane but Surface requires a quest item to access, you can gain these items by switching to the quest tab in the game area and completing quests, some items drop off regular creatures, some require quest chains to be completed.",
                      ),
                      React.createElement(
                          "p",
                          { className: "mb-4" },
                          "Some planes of existence like Shadow Planes, make character attacks weaker, while others like Hell and Purgatory will make your character over all, weaker. To offset this, there is",
                          " ",
                          React.createElement(
                              "a",
                              {
                                  href: "/information/gear-progression",
                                  target: "_blank",
                              },
                              "Gear Progression",
                              " ",
                              React.createElement("i", {
                                  className: "fas fa-external-link-alt",
                              }),
                          ),
                          " ",
                          "which if followed does help make these areas easier to farm valuable currencies and XP in.",
                      ),
                      React.createElement(
                          "div",
                          { className: "w-2/3" },
                          this.state.is_traversing
                              ? React.createElement(
                                    "span",
                                    {
                                        className:
                                            "text-orange-700 dark:text-orange-400",
                                    },
                                    "Traversing. One moment ...",
                                )
                              : React.createElement(Select, {
                                    onChange: this.setMap.bind(this),
                                    options: this.buildTraverseOptions(),
                                    menuPosition: "absolute",
                                    menuPlacement: "bottom",
                                    styles: {
                                        menuPortal: function (base) {
                                            return __assign(
                                                __assign({}, base),
                                                {
                                                    zIndex: 9999,
                                                    color: "#000000",
                                                },
                                            );
                                        },
                                    },
                                    menuPortalTarget: document.body,
                                    value: this.getDefaultValue(),
                                }),
                      ),
                      this.state.error_message !== null
                          ? React.createElement(
                                "p",
                                {
                                    className:
                                        "mt-4 mb-4 text-red-500 dark:text-red-400",
                                },
                                this.state.error_message,
                            )
                          : null,
                      this.state.is_traversing
                          ? React.createElement(LoadingProgressBar, null)
                          : null,
                  ),
        );
    };
    return TraverseModal;
})(React.Component);
export default TraverseModal;
//# sourceMappingURL=traverse-modal.js.map
