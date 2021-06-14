import React from 'react';
import {Row, Col} from 'react-bootstrap';

export default class MapActions extends React.Component {

  constructor(props) {
    super(props);
  }

  currentPosition() {
    return (
      <p className="text-left">
        X/Y: {this.props.characterPosition.x}/{this.props.characterPosition.y}
      </p>
    );
  }

  render() {
    return (
      <div className="character-position mt-2">
        <div className="mb-2 mt-2 clearfix">
          <Row>
            <Col xs={12} sm={12} md={4} lg={4} xl={4}>
              {this.currentPosition()}
            </Col>
            <Col xs={12} sm={12} md={8} lg={8} xl={8}>
              <div className="push-right">
                {
                  !_.isEmpty(this.props.adventures) ?
                    <button type="button" className=" btn btn-success mr-2 btn-sm "
                            onClick={this.props.openAdventureDetails}>
                      Adventure
                    </button>
                    : null
                }

                {
                  this.props.currentPort !== null ?
                    <button type="button" className=" btn btn-success mr-2 btn-sm "
                            disabled={this.props.disableMapButtons()} onClick={this.props.openPortDetails}>
                      Set Sail
                    </button>
                    : null
                }

                <button type="button" className="btn btn-primary btn-sm mr-2 " data-direction="teleport"
                        disabled={this.props.disableMapButtons()} onClick={this.props.openTeleport}>
                  Teleport
                </button>
              </div>
            </Col>
          </Row>
          <Row>
            <Col xs={12}>
              Characters on map: {this.props.charactersOnMap}
            </Col>
          </Row>
        </div>
      </div>
    )
  }
}
