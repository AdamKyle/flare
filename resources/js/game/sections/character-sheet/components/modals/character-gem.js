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
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import GemDetails from "../../../../components/modals/item-details/item-views/gem-details";
var CharacterGem = (function (_super) {
    __extends(CharacterGem, _super);
    function CharacterGem(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            gem_details: null,
        };
        return _this;
    }
    CharacterGem.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "character/" +
                    this.props.character_id +
                    "/gem-details/" +
                    this.props.slot_id,
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        gem_details: result.data.gem,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    CharacterGem.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.title,
            },
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : React.createElement(GemDetails, {
                      gem: this.state.gem_details,
                  }),
        );
    };
    return CharacterGem;
})(React.Component);
export default CharacterGem;
//# sourceMappingURL=character-gem.js.map
