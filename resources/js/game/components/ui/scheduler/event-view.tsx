import React, { Fragment } from "react";
import LoadingProgressBar from "../progress-bars/loading-progress-bar";
import EventViewProps from "./types/event-view-props";

export default class EventView extends React.Component<EventViewProps, {}> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                {this.props.deleting ? <LoadingProgressBar /> : null}
                <div className="my-4">
                    <p className="my-4">
                        {this.props.event.description.replace(
                            /(<([^>]+)>)/gi,
                            "",
                        )}
                    </p>

                    {this.props.event.raid_id !== null ? (
                        <p>
                            <a
                                href={
                                    "/information/raids/" +
                                    this.props.event.raid_id
                                }
                                target="_blank"
                            >
                                Vies Raid Details{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                            .
                        </p>
                    ) : null}
                </div>
            </Fragment>
        );
    }
}
