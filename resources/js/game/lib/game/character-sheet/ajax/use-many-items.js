import Ajax from "../../../ajax/ajax";
var UseManyItems = (function () {
    function UseManyItems(itemIds, component) {
        this.itemsToUse = itemIds;
        this.component = component;
    }
    UseManyItems.prototype.useAllItems = function (characterId) {
        var _this = this;
        new Ajax()
            .setRoute("character/" + characterId + "/inventory/use-many-items")
            .setParameters({ items_to_use: this.itemsToUse })
            .doAjaxCall(
                "post",
                function (result) {
                    _this.component.setState(
                        {
                            using_item: null,
                            loading: false,
                        },
                        function () {
                            _this.component.props.set_success_message(
                                "Used all selected items.",
                            );
                            _this.component.props.update_inventory(
                                result.data.inventory,
                            );
                            _this.component.props.manage_modal();
                        },
                    );
                },
                function (error) {
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.component.setState({
                            loading: false,
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    return UseManyItems;
})();
export default UseManyItems;
//# sourceMappingURL=use-many-items.js.map
