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
import React from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import KingdomDetailInfo from "../../../../components/kingdoms/modals/components/kingdom-details";
var KingdomDetails = (function (_super) {
    __extends(KingdomDetails, _super);
    function KingdomDetails(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            title: "",
            loading: true,
            npc_owned: false,
            action_in_progress: false,
            can_attack_kingdom: false,
        };
        return _this;
    }
    KingdomDetails.prototype.updateLoading = function (kingdomDetails) {
        var newState = {
            loading: false,
            title: this.buildTitle(kingdomDetails),
            npc_owned: kingdomDetails.is_npc_owned,
            can_attack_kingdom:
                kingdomDetails.is_enemy_kingdom ||
                kingdomDetails.is_npc_owned ||
                !kingdomDetails.is_protected,
        };
        var state = JSON.parse(JSON.stringify(this.state));
        this.setState(__assign(__assign({}, state), newState));
    };
    KingdomDetails.prototype.buildTitle = function (kingdomDetails) {
        var title =
            kingdomDetails.name +
            " (X/Y): " +
            kingdomDetails.x_position +
            "/" +
            kingdomDetails.y_position;
        if (kingdomDetails.is_npc_owned) {
            return title + " [NPC Owned]";
        }
        if (kingdomDetails.is_enemy_kingdom) {
            return title + " [Enemy]";
        }
        return title;
    };
    KingdomDetails.prototype.updateActionInProgress = function () {
        this.setState({
            action_in_progress: !this.state.action_in_progress,
        });
    };
    KingdomDetails.prototype.closeModal = function () {
        this.props.handle_close();
    };
    KingdomDetails.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: true,
                handle_close: this.props.handle_close,
                title: this.state.loading ? "One moment ..." : this.state.title,
                primary_button_disabled: this.state.action_in_progress,
            },
            React.createElement(KingdomDetailInfo, {
                kingdom_id: this.props.kingdom_id,
                character_id: this.props.character_id,
                update_loading: this.updateLoading.bind(this),
                show_top_section: this.props.show_top_section,
                allow_purchase: this.state.npc_owned,
                update_action_in_progress:
                    this.updateActionInProgress.bind(this),
                close_modal: this.closeModal.bind(this),
                can_attack_kingdom: this.state.can_attack_kingdom,
            }),
        );
    };
    return KingdomDetails;
})(React.Component);
export default KingdomDetails;
//# sourceMappingURL=kingdom-details.js.map
