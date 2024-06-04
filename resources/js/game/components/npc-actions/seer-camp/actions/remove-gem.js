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
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../lib/ajax/ajax";
import Select from "react-select";
import RemoveGemComparison from "../../../../sections/components/gems/remove-gem-comparison";
var RemoveGem = (function (_super) {
    __extends(RemoveGem, _super);
    function RemoveGem(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            fetching_data: true,
            removing_gem: false,
            selected_item: 0,
            items: [],
            gems: [],
            selected_gem_data: null,
        };
        return _this;
    }
    RemoveGem.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("seer-camp/gems-to-remove/" + this.props.character_id)
            .doAjaxCall("get", function (result) {
                _this.setState({
                    items: result.data.items,
                    gems: result.data.gems,
                    fetching_data: false,
                });
            });
    };
    RemoveGem.prototype.selectedItems = function (data) {
        if (data.value <= 0) {
            return;
        }
        var gemData = this.state.gems.find(function (gem) {
            return gem.slot_id === data.value;
        });
        if (typeof gemData === "undefined") {
            return;
        }
        this.setState({
            selected_item: data.value,
            selected_gem_data: gemData,
        });
    };
    RemoveGem.prototype.updateRemoveGemState = function (value, property) {
        this.setState(function (prevState) {
            var _a;
            return __assign(
                __assign({}, prevState),
                ((_a = {}), (_a[property] = value), _a),
            );
        });
    };
    RemoveGem.prototype.itemsToSelect = function () {
        var options = this.state.items.map(function (item) {
            return {
                label: item.name,
                value: item.slot_id,
            };
        });
        options.unshift({
            label: "Please select item",
            value: 0,
        });
        return options;
    };
    RemoveGem.prototype.selectedItem = function () {
        var _this = this;
        if (this.state.selected_item === 0) {
            return {
                label: "Please select item",
                value: 0,
            };
        }
        var item = this.state.items.find(function (item) {
            return item.slot_id === _this.state.selected_item;
        });
        if (typeof item === "undefined") {
            return {
                label: "Please select item",
                value: 0,
            };
        }
        return {
            label: item.name,
            value: item.slot_id,
        };
    };
    RemoveGem.prototype.getItemName = function () {
        var _this = this;
        var item = this.state.items.find(function (item) {
            return item.slot_id === _this.state.selected_item;
        });
        if (typeof item !== "undefined") {
            return item.name;
        }
        return null;
    };
    RemoveGem.prototype.render = function () {
        var _this = this;
        if (this.state.fetching_data) {
            return React.createElement(LoadingProgressBar, null);
        }
        if (this.state.selected_gem_data === null) {
            return React.createElement(Select, {
                onChange: this.selectedItems.bind(this),
                options: this.itemsToSelect(),
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
                value: this.selectedItem(),
            });
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(Select, {
                onChange: this.selectedItems.bind(this),
                options: this.itemsToSelect(),
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
                value: this.selectedItem(),
            }),
            this.state.selected_item !== 0
                ? React.createElement(RemoveGemComparison, {
                      comparison_data:
                          this.state.selected_gem_data.comparison
                              .atonement_changes,
                      original_atonement:
                          this.state.selected_gem_data.comparison
                              .original_atonement,
                      gems: this.state.selected_gem_data.gems,
                      character_id: this.props.character_id,
                      is_open: true,
                      item_name: this.getItemName(),
                      selected_item: this.state.selected_item,
                      update_parent: this.props.update_parent,
                      update_remomal_data: this.updateRemoveGemState.bind(this),
                      manage_modal: function () {
                          return _this.setState({
                              selected_item: 0,
                              selected_gem_data: null,
                          });
                      },
                  })
                : null,
        );
    };
    return RemoveGem;
})(React.Component);
export default RemoveGem;
//# sourceMappingURL=remove-gem.js.map
