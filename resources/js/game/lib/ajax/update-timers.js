import Ajax from "./ajax";
export var updateTimers = function (characterId) {
    new Ajax().setRoute("update-character-timers/" + characterId).doAjaxCall(
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
//# sourceMappingURL=update-timers.js.map
