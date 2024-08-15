import React from "react";
import PrimaryOutlineButton from "../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../ui/buttons/success-outline-button";
import OrangeOutlineButton from "../ui/buttons/orange-outline-button";
import WelcomeSection from "./sections/welcome-section";
import Fighting from "./sections/fighting";
import CraftingEnchanting from "./sections/crafting-enchanting";
import Quests from "./sections/quests";
import Events from "./sections/events";
import LetsGetGoing from "./sections/lets-get-going";

// Define the interface for the props
interface IntroSlidesProps {
    reset_show_intro: () => void;
}

// Define the interface for the state
interface IntroSlidesState {
    current_index: number;
}

export default class IntroSlides extends React.Component<
    IntroSlidesProps,
    IntroSlidesState
> {
    private sections: JSX.Element[];

    constructor(props: IntroSlidesProps) {
        super(props);
        this.state = {
            current_index: 0,
        };

        // Define the sections as an array of BasicCard components
        this.sections = [
            <WelcomeSection key="welcome" />,
            <Fighting key="fighting" />,
            <CraftingEnchanting key="crafting-enchanting" />,
            <Quests key="quests" />,
            <Events key="events" />,
            <LetsGetGoing key="lets-get-going" />,
        ];
    }

    componentDidMount() {
        // Add swipe event listeners for mobile support
        const swipeHandler = (event: TouchEvent) => {
            const xDist =
                event.changedTouches[0].clientX - event.touches[0].clientX;
            const direction = xDist > 0 ? "left" : "right";
            this.handleSwipe(direction);
        };

        document.addEventListener("touchstart", swipeHandler);
        document.addEventListener("touchend", swipeHandler);

        return () => {
            document.removeEventListener("touchstart", swipeHandler);
            document.removeEventListener("touchend", swipeHandler);
        };
    }

    nextSection = () => {
        this.setState((prevState) => ({
            current_index: Math.min(
                prevState.current_index + 1,
                this.sections.length - 1,
            ),
        }));
    };

    prevSection = () => {
        this.setState((prevState) => ({
            current_index: Math.max(prevState.current_index - 1, 0),
        }));
    };

    handleSwipe = (direction: string) => {
        if (direction === "left") {
            this.nextSection();
        } else if (direction === "right") {
            this.prevSection();
        }
    };

    render() {
        const { current_index } = this.state;
        const isLastSection = current_index === this.sections.length - 1;
        console.log(this.sections, current_index);
        return (
            <div
                className="flex items-center justify-center"
                style={{ height: "600px" }}
            >
                <div className="relative w-full max-w-4xl overflow-hidden">
                    <div
                        className="relative w-full flex transition-transform duration-500 ease-in-out"
                        style={{
                            transform: `translateX(-${current_index * 100}%)`,
                        }}
                    >
                        {this.sections.map((section) => (
                            <div
                                key={section.key}
                                className="relative w-full flex-shrink-0"
                            >
                                {section}

                                <div className="mt-4 flex justify-center w-full">
                                    {current_index > 0 && (
                                        <PrimaryOutlineButton
                                            button_label={"Back"}
                                            on_click={this.prevSection.bind(
                                                this,
                                            )}
                                            additional_css={"mr-2"}
                                        />
                                    )}

                                    {!isLastSection ? (
                                        <SuccessOutlineButton
                                            button_label={"Next"}
                                            on_click={this.nextSection.bind(
                                                this,
                                            )}
                                        />
                                    ) : (
                                        <OrangeOutlineButton
                                            button_label={"To the game!"}
                                            on_click={() =>
                                                this.props.reset_show_intro()
                                            }
                                        />
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }
}
