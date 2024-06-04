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
import CoreEventListener from "../../../../game/lib/game/event-listeners/core-event-listener";
var ShopListener = (function () {
    function ShopListener(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    ShopListener.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    ShopListener.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.shop = echo.private("update-shop-" + this.userId);
        } catch (e) {
            throw new Error(e);
        }
    };
    ShopListener.prototype.listen = function () {
        this.listenForShopUpdates();
    };
    ShopListener.prototype.listenForShopUpdates = function () {
        var _this = this;
        if (!this.shop) {
            return;
        }
        this.shop.listen("Game.Shop.Events.UpdateShopEvent", function (event) {
            if (!_this.component) {
                return;
            }
            _this.component.setState({
                gold: event.gold,
                inventory_count: event.inventoryCount,
            });
        });
    };
    ShopListener = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        ShopListener,
    );
    return ShopListener;
})();
export default ShopListener;
//# sourceMappingURL=shop-listener.js.map
