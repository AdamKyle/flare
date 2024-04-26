import React, { Fragment } from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";

export default class MakeCityACapitalModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: "",
        };
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Make Capital City"}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    handle_action: () => {},
                    secondary_button_disabled: this.state.loading,
                    secondary_button_label: "I am sure",
                }}
            >
                <p className="my-2">
                    Are you sure you want to make this kingdom your capital
                    city? You can only have city per plane as your capital city.
                </p>
                <p className="my-2">
                    Capital cities allow you to manage your other kingdoms on
                    the same plane, by issuing orders such as repair, upgrade
                    and recruit units.
                </p>
                <p className="my-2">
                    Should you make this city your capital city and it falls,
                    all your other kingdoms on the same plane will loose 55% of
                    their morale. You can reduce this through passive skills.
                </p>
            </Dialogue>
        );
    }
}
