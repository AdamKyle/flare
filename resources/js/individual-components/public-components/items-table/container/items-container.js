import { container } from "tsyringe";
import ItemTableAjax from "../ajax/item-table-ajax";
import ItemTableColumns from "../columns/item-table-columns";
var ItemsContainer = (function () {
    function ItemsContainer() {
        this.register("items-ajax", {
            useClass: ItemTableAjax,
        });
        this.register("items-table-columns", {
            useClass: ItemTableColumns,
        });
    }
    ItemsContainer.getInstance = function () {
        if (!ItemsContainer.instance) {
            ItemsContainer.instance = new ItemsContainer();
        }
        return ItemsContainer.instance;
    };
    ItemsContainer.prototype.fetch = function (token) {
        return container.resolve(token);
    };
    ItemsContainer.prototype.register = function (key, service) {
        container.register(key, { useValue: service });
    };
    return ItemsContainer;
})();
var dependencyRegistry;
var itemsTableServiceContainer = function () {
    if (!dependencyRegistry) {
        dependencyRegistry = new ItemsContainer();
    }
    return dependencyRegistry;
};
export { itemsTableServiceContainer, ItemsContainer };
//# sourceMappingURL=items-container.js.map
