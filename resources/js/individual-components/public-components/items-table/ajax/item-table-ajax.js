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
import Ajax from "../../../../game/lib/ajax/ajax.js";
import { TableType } from "../types/table-type";
var ItemTableAjax = (function () {
    function ItemTableAjax(ajax) {
        this.ajax = ajax;
    }
    ItemTableAjax.prototype.fetchTableData = function (component, type) {
        if (type === null) {
            component.setState({ loading: false });
            return;
        }
        if (type === TableType.CRAFTING) {
            return this.fetchCraftingTableItems(component);
        }
        var specialtyType = null;
        try {
            specialtyType = this.mapTypeToItemType(type);
        } catch (e) {
            component.setState({
                loading: false,
                error_message: e.message,
            });
        }
        if (specialtyType === null) {
            return;
        }
        this.fetchSpecialtyTypeItems(component, specialtyType);
    };
    ItemTableAjax.prototype.fetchCraftingTableItems = function (component) {
        this.ajax.setRoute("items-list").doAjaxCall(
            "get",
            function (result) {
                component.setState({
                    loading: false,
                    items: result.data.items,
                });
            },
            function (error) {
                component.setState({
                    loading: false,
                });
                if (typeof error.response !== "undefined") {
                    var response = error.response;
                    component.setState({
                        error_message: response.data.message,
                    });
                }
            },
        );
    };
    ItemTableAjax.prototype.fetchSpecialtyTypeItems = function (
        component,
        specialtyType,
    ) {
        this.ajax
            .setRoute("items-list-for-type")
            .setParameters({
                specialty_type: specialtyType,
            })
            .doAjaxCall(
                "get",
                function (result) {
                    component.setState({
                        loading: false,
                        items: result.data.items,
                    });
                },
                function (error) {
                    component.setState({
                        loading: false,
                    });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    ItemTableAjax.prototype.mapTypeToItemType = function (type) {
        switch (type) {
            case TableType.HELL_FORGED:
                return "Hell Forged";
            case TableType.PURGATORY_CHAINS:
                return "Purgatory Chains";
            case TableType.PIRATE_LORD_LEATHER:
                return "Pirate Lord Leather";
            case TableType.CORRUPTED_ICE:
                return "Corrupted Ice";
            case TableType.TWISTED_EARTH:
                return "Twisted Earth";
            case TableType.DELUSIONAL_SILVER:
                return "Delusional Silver";
            default:
                throw new Error("Unknown type of table to render.");
        }
    };
    ItemTableAjax = __decorate(
        [
            injectable(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Object]),
        ],
        ItemTableAjax,
    );
    return ItemTableAjax;
})();
export default ItemTableAjax;
//# sourceMappingURL=item-table-ajax.js.map
