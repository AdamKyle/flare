import { fetchCost } from "../lib/teleportion-costs";
var SetSailComponent = (function () {
    function SetSailComponent(component) {
        this.component = component;
    }
    SetSailComponent.prototype.setInitialCurrentSelectedPort = function () {
        var props = this.component.props;
        if (props.ports !== null) {
            this.setPort(props);
        }
    };
    SetSailComponent.prototype.updateSelectedCurrentPort = function () {
        var state = this.component.state;
        var props = this.component.props;
        if (state.current_port === null && props.ports !== null) {
            this.setPort(props);
        }
    };
    SetSailComponent.prototype.getDefaultPortValue = function () {
        var state = this.component.state;
        if (state.current_port !== null) {
            return {
                label:
                    state.current_port.name +
                    " (X/Y): " +
                    state.current_port.x +
                    "/" +
                    state.current_port.y,
                value: state.current_port.id,
            };
        }
        return { value: 0, label: "" };
    };
    SetSailComponent.prototype.buildSetSailOptions = function () {
        var props = this.component.props;
        if (props.ports !== null) {
            return props.ports.map(function (port) {
                return {
                    label: port.name + " (X/Y): " + port.x + "/" + port.y,
                    value: port.id,
                };
            });
        }
        return [];
    };
    SetSailComponent.prototype.setSelectedPortData = function (data) {
        var _this = this;
        var props = this.component.props;
        if (props.ports !== null) {
            var foundLocation = props.ports.filter(function (ports) {
                return ports.id === data.value;
            });
            if (foundLocation.length > 0) {
                this.component.setState(
                    {
                        x_position: foundLocation[0].x,
                        y_position: foundLocation[0].y,
                        current_location: foundLocation[0],
                        current_player_kingdom: null,
                        current_enemy_kingdom: null,
                    },
                    function () {
                        var state = _this.component.state;
                        var setSailCosts = fetchCost(
                            state.x_position,
                            state.y_position,
                            state.character_position,
                            props.currencies,
                        );
                        _this.component.setState(setSailCosts);
                    },
                );
            }
        }
    };
    SetSailComponent.prototype.setSail = function () {
        var props = this.component.props;
        var state = this.component.state;
        props.set_sail({
            x: state.x_position,
            y: state.y_position,
            cost: state.cost,
            timeout: state.time_out,
        });
        props.handle_close();
    };
    SetSailComponent.prototype.setPort = function (props) {
        if (props.ports === null) {
            return;
        }
        var foundLocation = props.ports.filter(function (port) {
            return (
                port.x === props.character_position.x &&
                port.y === props.character_position.y
            );
        });
        if (foundLocation.length > 0) {
            this.component.setState({
                current_port: foundLocation[0],
            });
        }
    };
    return SetSailComponent;
})();
export default SetSailComponent;
//# sourceMappingURL=set-sail-component.js.map
