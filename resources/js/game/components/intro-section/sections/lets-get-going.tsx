import React from "react";
import BasicCard from "../../ui/cards/basic-card";
import SectionProps from "./types/section-props";
import SectionState from "./types/section-state";

// Define an empty interface for props

export default class LetsGetGoing extends React.Component<
    SectionProps,
    SectionState
> {
    constructor(props: SectionProps) {
        super(props);

        this.state = {
            is_preview_open: false,
            preview_files: [
                {
                    url: this.getMainImageUrl("into-the-world-we-go"),
                    name: "The map is how we can move our character around!",
                },
            ],
        };
    }

    getMainImageUrl(name: string): string {
        const baseUrl = this.props.base_image_url;

        return `${baseUrl}/intro-section-images/in-to-the-game/${name}.png`;
    }

    managePreview() {
        this.props.preview_files(this.state.preview_files);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex flex-col md:flex-row items-center space-y-4 md:space-x-4 md:space-y-0 mt-8">
                    <div className="w-full md:w-1/2 flex flex-col justify-center pl-0 md:pl-4">
                        <h2 className="text-xl md:text-2xl font-bold dark:text-white text-black">
                            Into the World We Go!
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            There's so much to explore and do. The game might
                            seem overwhelming at first, but don’t worry—I’ve got
                            you covered! When you start, you'll see a modal
                            called The Guide.
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            The Guide consists of three parts: Requirements,
                            Story, and Instructions for mobile or desktop,
                            depending on your device. Follow this guide, engage
                            in chat, ask questions, and share feedback.
                            Together, Tlessa will grow and become one of the
                            best PBBGs out there!
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Let’s go, adventurer!
                        </p>
                    </div>
                    <div className="w-full md:w-1/2 flex flex-col items-center">
                        <img
                            src={this.getMainImageUrl("into-the-world-we-go")}
                            alt="Into the World"
                            className="w-48 h-36 md:w-[200px] md:h-[150px] object-cover border-4 dark:border-gray-700 border-gray-300 cursor-pointer"
                            onClick={this.managePreview.bind(this)}
                        />
                        <div className="mt-2 text-center italic text-sm">
                            Click/Tap to make larger
                        </div>
                    </div>
                </div>
            </BasicCard>
        );
    }
}
