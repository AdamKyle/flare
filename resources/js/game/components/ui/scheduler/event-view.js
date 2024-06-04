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
import LoadingProgressBar from "../progress-bars/loading-progress-bar";
var EventView = (function (_super) {
    __extends(EventView, _super);
    function EventView(props) {
        return _super.call(this, props) || this;
    }
    EventView.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.props.deleting
                ? React.createElement(LoadingProgressBar, null)
                : null,
            React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(
                    "p",
                    { className: "my-4" },
                    this.props.event.description.replace(/(<([^>]+)>)/gi, ""),
                ),
                this.props.event.raid_id !== null
                    ? React.createElement(
                          "p",
                          null,
                          React.createElement(
                              "a",
                              {
                                  href:
                                      "/information/raids/" +
                                      this.props.event.raid_id,
                                  target: "_blank",
                              },
                              "Vies Raid Details",
                              " ",
                              React.createElement("i", {
                                  className: "fas fa-external-link-alt",
                              }),
                          ),
                          ".",
                      )
                    : null,
            ),
        );
    };
    return EventView;
})(React.Component);
export default EventView;
//# sourceMappingURL=event-view.js.map
