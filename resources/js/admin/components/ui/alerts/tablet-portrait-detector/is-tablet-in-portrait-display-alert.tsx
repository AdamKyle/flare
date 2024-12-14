import React from "react";
import WarningAlert from "../simple-alerts/warning-alert";

export default class IsTabletInPortraitMode extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
        this.state = {
            isTabletPortrait: false,
        };
    }

    componentDidMount() {
        this.checkIsTabletPortrait(); // Initial check

        window.addEventListener("resize", this.checkIsTabletPortrait);
        window.addEventListener(
            "orientationchange",
            this.checkIsTabletPortrait,
        );
    }

    componentWillUnmount() {
        window.removeEventListener("resize", this.checkIsTabletPortrait);
        window.removeEventListener(
            "orientationchange",
            this.checkIsTabletPortrait,
        );
    }

    checkIsTabletPortrait = () => {
        const isPortrait = window.matchMedia("(orientation: portrait)").matches;
        const isTabletWidth = window.matchMedia(
            "(min-width: 820px) and (max-width: 1024px)",
        ).matches;
        this.setState({ isTabletPortrait: isPortrait && isTabletWidth });
    };

    render() {
        const { isTabletPortrait } = this.state;

        if (isTabletPortrait) {
            return (
                <WarningAlert>
                    You might have a better experience switching to portrait or
                    landscape mode. For example an iPad Mini is best in portrait
                    and an iPad Pro is best in landscape mode.
                </WarningAlert>
            );
        }

        return null;
    }
}
