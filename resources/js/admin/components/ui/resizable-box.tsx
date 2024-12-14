import React from "react";
import { ResizableBox as ReactResizableBox } from "react-resizable";
import ResizableBoxProps from "../../lib/ui/types/resizable-box-props";
import { viewPortWatcher } from "../../lib/view-port-watcher";

export default class ResizableBox extends React.Component<
    ResizableBoxProps,
    any
> {
    constructor(props: ResizableBoxProps) {
        super(props);

        this.state = {
            width: this.props.width,
            height: this.props.height,
            view_port: 0,
        };
    }

    componentDidMount() {
        viewPortWatcher(this);
    }

    componentDidUpdate() {
        if (!this.props.small_height) {
            return;
        }

        if (
            this.state.height !== this.props.small_height &&
            this.state.view_port <= 1024
        ) {
            this.setState({
                height: this.props.small_height,
            });
        }
    }

    onResize = (event: any, { element, size, handle }: any) => {
        this.setState({ width: size.width, height: size.height + 100 });
    };

    render() {
        return (
            <div>
                <ReactResizableBox
                    width={this.state.width}
                    height={this.state.height}
                    onResize={this.onResize}
                >
                    <div
                        style={{
                            ...this.props.style,
                            width: this.state.width + "px",
                            height: this.state.height + "px",
                        }}
                        className={this.props.additional_css}
                    >
                        {this.props.children}
                    </div>
                </ReactResizableBox>
            </div>
        );
    }
}
