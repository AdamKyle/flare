import React from "react";
import LinkButton from "../../../../ui/buttons/link-button";
import { ButtonVariant } from "../../../../ui/buttons/enums/button-variant-enum";
import MonsterTopSectionProps from "./types/monster-top-section-props";

export default class MonsterTopSection extends React.Component<MonsterTopSectionProps> {
    render() {
        return (
            <>
                <img
                    src={this.props.img_src}
                    alt="A cute cat"
                    className="
                        mx-auto mt-4 rounded-md drop-shadow-md
                        sm:w-64 md:w-72 lg:w-80 xl:w-96
                        transition-all duration-300 ease-in-out transform hover:scale-105
                        dark:drop-shadow-lg dark:border dark:border-gray-700 dark:bg-gray-800
                        focus:outline-none focus:ring-2 focus:ring-danube-500 focus:ring-offset-2 focus:ring-offset-white
                    "
                />
                <div
                    className="
                        mx-auto mt-4 flex items-center justify-center
                        w-full lg:w-1/3 gap-x-3 text-lg leading-none
                    "
                >
                    <button
                        className="text-xl"
                        aria-label="Previous"
                        onClick={() => this.props.next_action(1)}
                    >
                        <i
                            className="fas fa-chevron-circle-left"
                            aria-hidden="true"
                        ></i>
                    </button>
                    <span className="font-semibold">
                        {this.props.monster_name}
                    </span>
                    <button
                        className="text-xl"
                        aria-label="Next"
                        onClick={() => this.props.prev_action(1)}
                    >
                        <i
                            className="fas fa-chevron-circle-right"
                            aria-hidden="true"
                        ></i>
                    </button>
                </div>

                <div
                    className="
                        mx-auto mt-4 flex items-center justify-center
                        w-full lg:w-1/3 gap-x-3 text-lg leading-none
                    "
                >
                    <LinkButton
                        label="View Stats"
                        variant={ButtonVariant.PRIMARY}
                        on_click={() => this.props.view_stats_action(1)}
                    />
                </div>
            </>
        );
    }
}
