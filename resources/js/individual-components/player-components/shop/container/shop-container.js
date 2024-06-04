import ShopAjax from "../ajax/shop-ajax";
import { container } from "tsyringe";
import ShopTableColumns from "../shop-table/colums/shop-table-columns";
import ShopListener from "../event-listeners/shop-listener";
var ShopContainer = (function () {
    function ShopContainer() {
        this.register("shop-ajax", {
            useClass: ShopAjax,
        });
        this.register("shop-table-columns", {
            useClass: ShopTableColumns,
        });
        this.register("ShopListenerDefinition", {
            useClass: ShopListener,
        });
    }
    ShopContainer.getInstance = function () {
        if (!ShopContainer.instance) {
            ShopContainer.instance = new ShopContainer();
        }
        return ShopContainer.instance;
    };
    ShopContainer.prototype.fetch = function (token) {
        return container.resolve(token);
    };
    ShopContainer.prototype.register = function (key, service) {
        container.register(key, { useValue: service });
    };
    return ShopContainer;
})();
var dependencyRegistry;
var shopServiceContainer = function () {
    if (!dependencyRegistry) {
        dependencyRegistry = new ShopContainer();
    }
    return dependencyRegistry;
};
export { shopServiceContainer, ShopContainer };
//# sourceMappingURL=shop-container.js.map
