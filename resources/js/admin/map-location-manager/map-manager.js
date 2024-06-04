var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
import React, { Component } from "react";
import GridOverlay from "./grid-overlay";
import LoadingProgressBar from "../../game/components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../game/components/ui/alerts/simple-alerts/danger-alert";
import InitializeMapAjax from "./ajax/initialize-map-ajax";
import { gridOverLayContainer } from "./container/grid-overlay-container";
var MapManager = (function (_super) {
    __extends(MapManager, _super);
    function MapManager(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            imgSrc: null,
            coordinates: { x: [], y: [] },
            locations: [],
            error_message: null,
        };
        _this.initializeMap = gridOverLayContainer().fetch(InitializeMapAjax);
        return _this;
    }
    MapManager.prototype.componentDidMount = function () {
        this.initializeMap.initializeMap(this, this.props.mapId);
    };
    MapManager.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        if (this.state.error_message !== null) {
            return React.createElement(DangerAlert, null, this.state.error_message);
        }
        return (React.createElement("div", null,
            React.createElement(GridOverlay, { coordinates: this.state.coordinates, mapSrc: this.state.imgSrc, locations: this.state.locations })));
    };
    return MapManager;
}(Component));
export default MapManager;
//# sourceMappingURL=map-manager.js.map