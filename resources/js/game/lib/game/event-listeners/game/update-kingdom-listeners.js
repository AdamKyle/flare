var __decorate =
    (this && this.__decorate) ||
    function (decorators, target, key, desc) {
        var c = arguments.length,
            r =
                c < 3
                    ? target
                    : desc === null
                      ? (desc = Object.getOwnPropertyDescriptor(target, key))
                      : desc,
            d;
        if (
            typeof Reflect === "object" &&
            typeof Reflect.decorate === "function"
        )
            r = Reflect.decorate(decorators, target, key, desc);
        else
            for (var i = decorators.length - 1; i >= 0; i--)
                if ((d = decorators[i]))
                    r =
                        (c < 3
                            ? d(r)
                            : c > 3
                              ? d(target, key, r)
                              : d(target, key)) || r;
        return c > 3 && r && Object.defineProperty(target, key, r), r;
    };
var __metadata =
    (this && this.__metadata) ||
    function (k, v) {
        if (
            typeof Reflect === "object" &&
            typeof Reflect.metadata === "function"
        )
            return Reflect.metadata(k, v);
    };
var __param =
    (this && this.__param) ||
    function (paramIndex, decorator) {
        return function (target, key) {
            decorator(target, key, paramIndex);
        };
    };
import { inject, injectable } from "tsyringe";
import Kingdom from "../../../../components/kingdoms/kingdom";
import SmallKingdom from "../../../../components/kingdoms/small-kingdom";
import CoreEventListener from "../core-event-listener";
var UpdateKingdomListeners = (function () {
    function UpdateKingdomListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    UpdateKingdomListeners.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    UpdateKingdomListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.kingdomUpdates = echo.private("update-kingdom-" + this.userId);
        } catch (e) {
            throw new Error(e);
        }
    };
    UpdateKingdomListeners.prototype.listen = function () {
        this.listenToKingdomUpdates();
    };
    UpdateKingdomListeners.prototype.listenToKingdomUpdates = function () {
        var _this = this;
        if (!this.kingdomUpdates) {
            return;
        }
        this.kingdomUpdates.listen(
            "Game.Kingdoms.Events.UpdateKingdom",
            function (event) {
                if (!_this.component) {
                    return;
                }
                if (_this.component instanceof Kingdom) {
                    if (_this.component.state.kingdom === null) {
                        return;
                    }
                    if (event.kingdom.id === _this.component.state.kingdom.id) {
                        _this.component.setState({
                            kingdom: event.kingdom,
                        });
                    }
                }
                if (_this.component instanceof SmallKingdom) {
                    if (_this.component.state.kingdom === null) {
                        return;
                    }
                    if (event.kingdom.id === _this.component.state.kingdom.id) {
                        _this.component.setState({
                            kingdom: event.kingdom,
                        });
                    }
                }
            },
        );
    };
    UpdateKingdomListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        UpdateKingdomListeners,
    );
    return UpdateKingdomListeners;
})();
export default UpdateKingdomListeners;
//# sourceMappingURL=update-kingdom-listeners.js.map
