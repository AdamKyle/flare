import React from "react";
import { renderIcons } from "./helpers/render-icons";
import StationarySpinSectionProps from "./types/stationary-spin-section-props";

export default class StationarySpinSection extends React.Component<
    StationarySpinSectionProps,
    {}
> {
    constructor(props: StationarySpinSectionProps) {
        super(props);
    }

    render() {
        return (
            <div className="max-h-[150px] overflow-hidden mt-4">
                <div className="grid grid-cols-3">
                    <div>
                        {renderIcons(
                            this.props.roll.length > 0 ? this.props.roll[0] : 0,
                            this.props.icons,
                        )}
                    </div>
                    <div>
                        {renderIcons(
                            this.props.roll.length > 0 ? this.props.roll[1] : 0,
                            this.props.icons,
                        )}
                    </div>
                    <div>
                        {renderIcons(
                            this.props.roll.length > 0 ? this.props.roll[2] : 0,
                            this.props.icons,
                        )}
                    </div>
                </div>
            </div>
        );
    }
}
