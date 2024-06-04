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
import RenderAtonementDetails from "../../../../sections/components/gems/components/render-atonement-details";
import WarningAlert from "../../../ui/alerts/simple-alerts/warning-alert";
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
var CharacterElementalAtonementSection = (function (_super) {
    __extends(CharacterElementalAtonementSection, _super);
    function CharacterElementalAtonementSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_loading: true,
            elemental_atonement: null,
            error_message: "",
        };
        return _this;
    }
    CharacterElementalAtonementSection.prototype.componentDidMount =
        function () {
            var _this = this;
            new Ajax()
                .setRoute(
                    "character-sheet/" +
                        this.props.character.id +
                        "/elemental-atonement-info",
                )
                .doAjaxCall(
                    "get",
                    function (response) {
                        _this.setState({
                            is_loading: false,
                            elemental_atonement:
                                response.data.elemental_atonement_details
                                    .elemental_atonement,
                        });
                    },
                    function (error) {
                        _this.setState({
                            is_loading: false,
                        });
                        if (typeof error.response !== "undefined") {
                            var response = error.response;
                            _this.setState({
                                error_message: response.data.message,
                            });
                        }
                    },
                );
        };
    CharacterElementalAtonementSection.prototype.render = function () {
        if (this.state.is_loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "p",
                { className: "my-4" },
                "Your atonement is calculated based off the gems you have socketed onto your gear. The highest elemental value is your damage, where as the rest are used as resistances against that type of elemental damage.",
            ),
            this.state.elemental_atonement === null
                ? React.createElement(
                      WarningAlert,
                      null,
                      "You have nothing equipped. Cannot calculate your Elemental Atonement. Learn more",
                      " ",
                      React.createElement(
                          "a",
                          { href: "/information/atonement", target: "_blank" },
                          "here: Atonement",
                          " ",
                          React.createElement("i", {
                              className: "fas fa-external-link-alt",
                          }),
                      ),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      React.createElement(RenderAtonementDetails, {
                          original_atonement: this.state.elemental_atonement,
                      }),
                      React.createElement("div", {
                          className:
                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                      }),
                      React.createElement(
                          "h4",
                          { className: "my-4" },
                          "Elemental Damage",
                      ),
                      React.createElement(
                          "dl",
                          null,
                          React.createElement("dt", null, "Element: "),
                          React.createElement(
                              "dd",
                              null,
                              this.state.elemental_atonement.highest_element
                                  .name,
                          ),
                          React.createElement("dt", null, "Damage: "),
                          React.createElement(
                              "dd",
                              null,
                              (
                                  this.state.elemental_atonement.highest_element
                                      .damage * 100
                              ).toFixed(2),
                              "%",
                          ),
                      ),
                      React.createElement(
                          "p",
                          { className: "my-4" },
                          "Your elemental damage is a % of damage you will deal as that element in addition to your other attacks when you attack an enemy. You can learn more about it",
                          " ",
                          React.createElement(
                              "a",
                              {
                                  href: "/information/atonement",
                                  target: "_blank",
                              },
                              "here: Atonement",
                              " ",
                              React.createElement("i", {
                                  className: "fas fa-external-link-alt",
                              }),
                          ),
                      ),
                  ),
        );
    };
    return CharacterElementalAtonementSection;
})(React.Component);
export default CharacterElementalAtonementSection;
//# sourceMappingURL=character-elemental-atonement-section.js.map
