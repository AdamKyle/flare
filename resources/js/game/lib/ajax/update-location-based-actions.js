import Ajax from "./ajax";
export var updateLocationBasedActions = function (characterId) {
    new Ajax()
        .setRoute("map/update-character-location-actions/" + characterId)
        .doAjaxCall(
            "get",
            function (result) {},
            function (error) {
                if (error.hasOwnProperty("response")) {
                    var response = error.response;
                    if (typeof response === "undefined") {
                        return;
                    }
                    if (response.status === 401) {
                        return location.reload();
                    }
                }
            },
        );
};
//# sourceMappingURL=update-location-based-actions.js.map
