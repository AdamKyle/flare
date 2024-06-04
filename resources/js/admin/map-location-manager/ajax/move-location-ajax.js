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
import Ajax from "../../../game/lib/ajax/ajax";
var MoveLocationAjax = (function () {
    function MoveLocationAjax(ajax) {
        this.ajax = ajax;
    }
    MoveLocationAjax.prototype.moveLocation = function (
        component,
        locationId,
        coordinates,
    ) {
        this.ajax
            .setRoute("admin/map-manager/move-location/" + locationId)
            .setParameters({
                x: coordinates.x,
                y: coordinates.y,
            })
            .doAjaxCall(
                "post",
                function (result) {
                    var coordinates = {
                        x: result.data.x_coordinates,
                        y: result.data.y_coordinates,
                    };
                    component.setState(
                        {
                            processing: false,
                        },
                        function () {
                            component.props.updateLocations(
                                result.data.locations,
                            );
                            component.props.closeModal();
                        },
                    );
                },
                function (error) {
                    component.setState({
                        processing: false,
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
    MoveLocationAjax = __decorate(
        [
            injectable(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Object]),
        ],
        MoveLocationAjax,
    );
    return MoveLocationAjax;
})();
export default MoveLocationAjax;
//# sourceMappingURL=move-location-ajax.js.map
