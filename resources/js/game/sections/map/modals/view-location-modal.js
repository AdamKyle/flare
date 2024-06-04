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
import LocationDetails from "./components/view-details/location-details";
import KingdomDetails from "./components/view-details/kingdom-details";
var ViewLocationModal = (function (_super) {
    __extends(ViewLocationModal, _super);
    function ViewLocationModal(props) {
        return _super.call(this, props) || this;
    }
    ViewLocationModal.prototype.renderModal = function () {
        if (this.props.location !== null) {
            return React.createElement(LocationDetails, {
                location: this.props.location,
                handle_close: this.props.handle_close,
            });
        }
        if (this.props.player_kingdom_id !== null) {
            return React.createElement(KingdomDetails, {
                kingdom_id: this.props.player_kingdom_id,
                character_id: this.props.character_id,
                show_top_section: true,
                handle_close: this.props.handle_close,
            });
        }
        if (this.props.enemy_kingdom_id !== null) {
            return React.createElement(KingdomDetails, {
                kingdom_id: this.props.enemy_kingdom_id,
                character_id: this.props.character_id,
                show_top_section: false,
                handle_close: this.props.handle_close,
            });
        }
        if (this.props.npc_kingdom_id !== null) {
            return React.createElement(KingdomDetails, {
                kingdom_id: this.props.npc_kingdom_id,
                character_id: this.props.character_id,
                show_top_section: false,
                handle_close: this.props.handle_close,
            });
        }
        return null;
    };
    ViewLocationModal.prototype.render = function () {
        return this.renderModal();
    };
    return ViewLocationModal;
})(React.Component);
export default ViewLocationModal;
//# sourceMappingURL=view-location-modal.js.map
