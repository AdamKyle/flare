import Ajax from "../../../../lib/ajax/ajax";
var SeerActions = (function () {
    function SeerActions() {}
    SeerActions.handleInitialFetch = function (component) {
        new Ajax()
            .setRoute("visit-seer-camp/" + component.props.character_id)
            .doAjaxCall(
                "get",
                function (result) {
                    component.setState({
                        items: result.data.items,
                        gems: result.data.gems,
                        is_loading: false,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    SeerActions.manageSocketsOnItem = function (component, slotId) {
        new Ajax()
            .setRoute("seer-camp/add-sockets/" + component.props.character_id)
            .setParameters({ slot_id: slotId })
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState({
                        items: result.data.items,
                        gems: result.data.gems,
                        trading_with_seer: false,
                        success_message: result.data.message,
                    });
                },
                function (error) {
                    component.setState(
                        {
                            trading_with_seer: false,
                        },
                        function () {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                component.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
                },
            );
    };
    SeerActions.attachGemToItem = function (component, slotId, gemSlotId) {
        new Ajax()
            .setRoute("seer-camp/add-gem/" + component.props.character_id)
            .setParameters({ slot_id: slotId, gem_slot_id: gemSlotId })
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState(
                        {
                            trading_with_seer: false,
                        },
                        function () {
                            component.props.update_parent(
                                result.data.message,
                                "success_message",
                            );
                            component.props.update_parent(
                                result.data.items,
                                "items",
                            );
                            component.props.update_parent(
                                result.data.gems,
                                "gems",
                            );
                            component.props.manage_model();
                        },
                    );
                },
                function (error) {
                    component.setState(
                        {
                            trading_with_seer: false,
                        },
                        function () {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                component.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
                },
            );
    };
    SeerActions.replaceGemOnItem = function (
        component,
        slotId,
        gemSlotId,
        gemSocketId,
    ) {
        new Ajax()
            .setRoute("seer-camp/replace-gem/" + component.props.character_id)
            .setParameters({
                slot_id: slotId,
                gem_slot_id: gemSlotId,
                gem_slot_to_replace: gemSocketId,
            })
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState(
                        {
                            is_replacing: false,
                        },
                        function () {
                            component.props.update_parent(
                                result.data.message,
                                "success_message",
                            );
                            component.props.update_parent(
                                result.data.items,
                                "items",
                            );
                            component.props.update_parent(
                                result.data.gems,
                                "gems",
                            );
                            component.closeModals();
                        },
                    );
                },
                function (error) {
                    component.setState(
                        {
                            is_replacing: false,
                        },
                        function () {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                component.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
                },
            );
    };
    SeerActions.removeGem = function (component, slotId, gemId) {
        new Ajax()
            .setRoute("seer-camp/remove-gem/" + component.props.character_id)
            .setParameters({ slot_id: slotId, gem_id: gemId })
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState(
                        {
                            is_removing: false,
                        },
                        function () {
                            component.props.update_parent(
                                result.data.message,
                                "success_message",
                            );
                            component.props.update_parent(
                                result.data.items,
                                "items",
                            );
                            component.props.update_parent(
                                result.data.gems,
                                "gems",
                            );
                            component.props.update_remomal_data(
                                result.data.removal_data.items,
                                "items",
                            );
                            component.props.update_remomal_data(
                                result.data.removal_data.gems,
                                "gems",
                            );
                            component.props.manage_modal();
                        },
                    );
                },
                function (error) {
                    component.setState(
                        {
                            is_removing: false,
                        },
                        function () {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                component.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
                },
            );
    };
    SeerActions.removeAllGems = function (component, slotId) {
        new Ajax()
            .setRoute(
                "seer-camp/remove-all-gems/" +
                    component.props.character_id +
                    "/" +
                    slotId,
            )
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState(
                        {
                            is_removing: false,
                        },
                        function () {
                            component.props.update_parent(
                                result.data.message,
                                "success_message",
                            );
                            component.props.update_parent(
                                result.data.items,
                                "items",
                            );
                            component.props.update_parent(
                                result.data.gems,
                                "gems",
                            );
                            component.props.update_remomal_data(
                                result.data.removal_data.items,
                                "items",
                            );
                            component.props.update_remomal_data(
                                result.data.removal_data.gems,
                                "gems",
                            );
                            component.props.manage_modal();
                        },
                    );
                },
                function (error) {
                    component.setState(
                        {
                            is_removing: false,
                        },
                        function () {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                component.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
                },
            );
    };
    return SeerActions;
})();
export default SeerActions;
//# sourceMappingURL=seer-actions.js.map
