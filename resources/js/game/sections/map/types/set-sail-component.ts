import { fetchCost } from "../lib/teleportion-costs";
import SetSailModal from "../modals/set-sail-modal";
import LocationDetails from "./location-details";
import SetSailModalProps from "./map/modals/set-sail-modal-props";

export default class SetSailComponent {
    private component: SetSailModal;

    constructor(component: SetSailModal) {
        this.component = component;
    }

    /**
     * Set the initial selected port.
     */
    public setInitialCurrentSelectedPort() {
        const props = this.component.props;

        if (props.ports !== null) {
            this.setPort(props);
        }
    }

    /**
     * Update the port when the user selects a new one.
     */
    public updateSelectedCurrentPort() {
        const state = this.component.state;
        const props = this.component.props;

        if (state.current_port === null && props.ports !== null) {
            this.setPort(props);
        }
    }

    /**
     * Get the default port value.
     */
    public getDefaultPortValue(): { label: string; value: number } {
        const state = this.component.state;

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
    }

    /**
     * Build the select options for the ports.
     */
    public buildSetSailOptions(): { value: number; label: string }[] | [] {
        const props = this.component.props;

        if (props.ports !== null) {
            return props.ports.map((port: LocationDetails) => {
                return {
                    label: port.name + " (X/Y): " + port.x + "/" + port.y,
                    value: port.id,
                };
            });
        }

        return [];
    }

    /**
     * Set the selected port data and its associated cost.
     *
     * @param data
     */
    public setSelectedPortData(data: { label: string; value: number }) {
        const props = this.component.props;

        if (props.ports !== null) {
            const foundLocation = props.ports.filter(
                (ports: LocationDetails) => ports.id === data.value,
            );

            if (foundLocation.length > 0) {
                this.component.setState(
                    {
                        x_position: foundLocation[0].x,
                        y_position: foundLocation[0].y,
                        current_location: foundLocation[0],
                        current_player_kingdom: null,
                        current_enemy_kingdom: null,
                        current_port: foundLocation[0],
                    },
                    () => {
                        const state = this.component.state;

                        const setSailCosts = fetchCost(
                            state.x_position,
                            state.y_position,
                            state.character_position,
                            props.currencies,
                        );

                        this.component.setState(setSailCosts);
                    },
                );
            }
        }
    }

    /**
     * Set sail to the new port.
     */
    public setSail() {
        const props = this.component.props;
        const state = this.component.state;

        props.set_sail({
            x: state.x_position,
            y: state.y_position,
            cost: state.cost,
            timeout: state.time_out,
        });

        props.handle_close();
    }

    /**
     * Set the port data.
     *
     * @param props
     * @private
     */
    private setPort(props: SetSailModalProps) {
        if (props.ports === null) {
            return;
        }

        const foundLocation = props.ports.filter(
            (port: LocationDetails) =>
                port.x === props.character_position.x &&
                port.y === props.character_position.y,
        );

        if (foundLocation.length > 0) {
            this.component.setState({
                current_port: foundLocation[0],
            });
        }
    }
}
