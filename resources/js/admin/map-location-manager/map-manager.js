var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React, { Component } from "react";
import GridOverlay from "./grid-overlay";
import Ajax from "../../game/lib/ajax/ajax";
import LoadingProgressBar from "../../game/components/ui/progress-bars/loading-progress-bar";
var MapManager = (function (_super) {
    __extends(MapManager, _super);
    function MapManager(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            imgSrc: null,
            coordinates: { x: [], y: [] },
            locations: [],
        };
        return _this;
    }
    MapManager.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax().setRoute("admin/map-manager/" + this.props.mapId).doAjaxCall(
            "get",
            function (result) {
                var coordinates = {
                    x: result.data.x_coordinates,
                    y: result.data.y_coordinates,
                };
                _this.setState({
                    loading: false,
                    imgSrc: result.data.path,
                    coordinates: coordinates,
                    locations: result.data.locations,
                });
            },
            function (error) {},
        );
    };
    MapManager.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(GridOverlay, {
                coordinates: this.state.coordinates,
                mapSrc: this.state.imgSrc,
                locations: this.state.locations,
            }),
        );
    };
    return MapManager;
})(Component);
export default MapManager;
//# sourceMappingURL=map-manager.js.map
