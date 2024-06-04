import Ajax from "./ajax";
export var generateServerMessage = function (type, customMessage) {
    new Ajax()
        .setRoute("server-message")
        .setParameters({
            type: type,
            custom_message: customMessage,
        })
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
//# sourceMappingURL=generate-server-message.js.map
