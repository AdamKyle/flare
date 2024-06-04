import { movePlayer } from "../move-player";
import { generateServerMessage } from "../../../../lib/ajax/generate-server-message";
import Ajax from "../../../../lib/ajax/ajax";
var MovePlayer = (function () {
    function MovePlayer(component) {
        this.component = component;
        this.characterPosition = null;
        this.mapPosition = null;
    }
    MovePlayer.prototype.setCharacterPosition = function (characterPosition) {
        this.characterPosition = characterPosition;
        return this;
    };
    MovePlayer.prototype.setMapPosition = function (mapPosition) {
        this.mapPosition = mapPosition;
        return this;
    };
    MovePlayer.prototype.movePlayer = function (
        characterId,
        direction,
        component,
    ) {
        var _this = this;
        if (this.characterPosition === null || this.mapPosition === null) {
            this.component.setState({
                can_player_move: true,
            });
            return generateServerMessage("cant_move");
        }
        var playerPosition = movePlayer(
            this.characterPosition.x,
            this.characterPosition.y,
            direction,
        );
        if (!playerPosition) {
            return generateServerMessage("cant_move");
        }
        new Ajax()
            .setRoute("move/" + characterId)
            .setParameters({
                position_x: this.mapPosition.x,
                position_y: this.mapPosition.y,
                character_position_x: playerPosition.x,
                character_position_y: playerPosition.y,
            })
            .doAjaxCall(
                "post",
                function (result) {
                    component.props.update_map_state(result.data);
                },
                function (error) {
                    _this.handleErrors(error);
                },
            );
    };
    MovePlayer.prototype.teleportPlayer = function (
        data,
        characterId,
        updateMapState,
    ) {
        var _this = this;
        new Ajax()
            .setRoute("map/teleport/" + characterId)
            .setParameters(data)
            .doAjaxCall(
                "post",
                function (result) {
                    updateMapState(result.data);
                },
                function (error) {
                    _this.handleErrors(error);
                },
            );
    };
    MovePlayer.prototype.setSail = function (
        data,
        characterId,
        viewPort,
        updateMapState,
    ) {
        var _this = this;
        new Ajax()
            .setRoute("map/set-sail/" + characterId)
            .setParameters(data)
            .doAjaxCall(
                "post",
                function (result) {
                    updateMapState(result.data);
                },
                function (error) {
                    _this.handleErrors(error);
                },
            );
    };
    MovePlayer.prototype.handleErrors = function (error) {
        var response = error.response;
        if (typeof response === "undefined") {
            return;
        }
        if (response.status === 401) {
            return location.reload();
        }
    };
    return MovePlayer;
})();
export default MovePlayer;
//# sourceMappingURL=move-player.js.map
