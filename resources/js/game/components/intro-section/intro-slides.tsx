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
import IntroImagePreviewer from "./sections/image-previewer/intro-image-previewer";
import clsx from "clsx";

// Define the interface for the props
interface IntroSlidesProps {
    reset_show_intro: () => void;
    view_port: number;
}

interface PreviewFiles {
    url: string;
    name: string;
}

// Define the interface for the state
interface IntroSlidesState {
    current_index: number;
    is_preview_open: boolean;
    preview_files: PreviewFiles[] | [];
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
            preview_files: [],
            is_preview_open: false,
        };

        // Define the sections as an array of BasicCard components
        this.sections = [
            <WelcomeSection
                key="welcome"
                show_small_image={this.props.view_port <= 932}
                base_image_url={import.meta.env.VITE_BASE_IMAGE_URL}
                preview_files={this.previewFiles.bind(this)}
            />,
            <Fighting
                key="fighting"
                show_small_image={this.props.view_port <= 932}
                base_image_url={import.meta.env.VITE_BASE_IMAGE_URL}
                preview_files={this.previewFiles.bind(this)}
            />,
            <CraftingEnchanting
                key="crafting-enchanting"
                show_small_image={this.props.view_port <= 932}
                base_image_url={import.meta.env.VITE_BASE_IMAGE_URL}
                preview_files={this.previewFiles.bind(this)}
            />,
            <Quests
                key="quests"
                show_small_image={this.props.view_port <= 932}
                base_image_url={import.meta.env.VITE_BASE_IMAGE_URL}
                preview_files={this.previewFiles.bind(this)}
            />,
            <Events
                key="events"
                show_small_image={this.props.view_port <= 932}
                base_image_url={import.meta.env.VITE_BASE_IMAGE_URL}
                preview_files={this.previewFiles.bind(this)}
            />,
            <LetsGetGoing
                key="lets-get-going"
                show_small_image={this.props.view_port <= 932}
                base_image_url={import.meta.env.VITE_BASE_IMAGE_URL}
                preview_files={this.previewFiles.bind(this)}
            />,
        ];
    }

    previewFiles(filesToPreview?: PreviewFiles[]) {
        if (!this.state.is_preview_open && filesToPreview) {
            this.setState({
                preview_files: filesToPreview,
                is_preview_open: true,
            });

            return;
        }

        this.setState({
            preview_files: [],
            is_preview_open: false,
        });
    }

    nextSection() {
        this.setState((prevState) => ({
            current_index: Math.min(
                prevState.current_index + 1,
                this.sections.length - 1,
            ),
        }));
    }

    prevSection() {
        this.setState((prevState) => ({
            current_index: Math.max(prevState.current_index - 1, 0),
        }));
    }

    handleSwipe(direction: string) {
        if (direction === "left") {
            this.nextSection();
        } else if (direction === "right") {
            this.prevSection();
        }
    }

    render() {
        const { current_index } = this.state;
        const isLastSection = current_index === this.sections.length - 1;

        return (
            <div>
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
                            {this.sections.map((section, index) => (
                                <div
                                    key={section.key}
                                    className="relative w-full flex-shrink-0"
                                >
                                    <div
                                        key={section.key}
                                        className={clsx(
                                            "relative w-full flex-shrink-0",
                                            {
                                                hidden: index !== current_index,
                                                block: index === current_index,
                                            },
                                        )}
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
                                                    button_label={
                                                        "To the game!"
                                                    }
                                                    on_click={() =>
                                                        this.props.reset_show_intro()
                                                    }
                                                />
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
                {this.state.is_preview_open && (
                    <IntroImagePreviewer
                        files={this.state.preview_files}
                        is_open={this.state.is_preview_open}
                        on_close={this.previewFiles.bind(this)}
                    />
                )}
            </div>
        );
    }
}
