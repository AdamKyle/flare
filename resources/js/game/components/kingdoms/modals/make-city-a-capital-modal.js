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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
var MakeCityACapitalModal = (function (_super) {
    __extends(MakeCityACapitalModal, _super);
    function MakeCityACapitalModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            error_message: null,
        };
        return _this;
    }
    MakeCityACapitalModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Make Capital City",
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    handle_action: function () {},
                    secondary_button_disabled: this.state.loading,
                    secondary_button_label: "I am sure",
                },
            },
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-2" },
                      this.state.error_message,
                  )
                : null,
            React.createElement(
                "p",
                { className: "my-2" },
                "Are you sure you want to make this kingdom your capital city? You can only have city per plane as your capital city.",
            ),
            React.createElement(
                "p",
                { className: "my-2" },
                "Capital cities allow you to manage your other kingdoms on the same plane, by issuing orders such as repair, upgrade and recruit units.",
            ),
            React.createElement(
                "p",
                { className: "my-2" },
                "Should you make this city your capital city and it falls, all your other kingdoms on the same plane will loose 55% of their morale. You can reduce this through passive skills.",
            ),
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return MakeCityACapitalModal;
})(React.Component);
export default MakeCityACapitalModal;
//# sourceMappingURL=make-city-a-capital-modal.js.map
