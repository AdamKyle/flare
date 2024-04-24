import React from "react";
import clsx from "clsx";
import SuccessButton from "../../../../components/ui/buttons/success-button";
import SpinSectionProps from "./types/spin-section-props";
import { renderIcons } from "./helpers/render-icons";

export default class SpinSection extends React.Component<SpinSectionProps, {}> {
    constructor(props: SpinSectionProps) {
        super(props);
    }

    renderIcons(index: number) {
        const icon = this.props.icons[index];

        return (
            <div className="text-center mb-10">
                <i
                    className={icon.icon + " text-7xl"}
                    style={{ color: icon.color }}
                ></i>
                <p className="text-lg mt-2">{icon.title}</p>
            </div>
        );
    }

    render() {
        return (
            <div
                className={clsx("max-w-[450px] m-auto lg:mr-auto", {
                    "ml-[150px]": !this.props.is_small,
                })}
            >
                <div className="max-h-[150px] overflow-hidden mt-4">
                    <div className="grid grid-cols-3">
                        <div>
                            {renderIcons(
                                this.props.spinning_indexes[0],
                                this.props.icons,
                            )}
                        </div>
                        <div>
                            {renderIcons(
                                this.props.spinning_indexes[1],
                                this.props.icons,
                            )}
                        </div>
                        <div>
                            {renderIcons(
                                this.props.spinning_indexes[2],
                                this.props.icons,
                            )}
                        </div>
                    </div>
                </div>
                <div className="text-center">
                    <SuccessButton
                        button_label={"Spin"}
                        on_click={this.props.spin_action}
                        disabled={true}
                    />
                </div>
            </div>
        );
    }
}
