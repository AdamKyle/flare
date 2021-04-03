import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import ContentLoader from 'react-content-loader';

export default class LoadingModal extends React.Component {

  render() {
    return (
      <Modal onHide={this.props.close} backdrop="static" keyboard={false} show={this.props.show}>
        <Modal.Header closeButton>
          <Modal.Title>{this.props.loadingText}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <ContentLoader viewBox="0 0 380 300">
            {/* Only SVG shapes */}
            <rect x="0" y="0" rx="4" ry="4" width="500" height="230"/>
            <rect x="0" y="245" rx="3" ry="3" width="500" height="10"/>
            <rect x="0" y="265" rx="3" ry="3" width="500" height="10"/>
            <rect x="0" y="285" rx="3" ry="3" width="500" height="10"/>
          </ContentLoader>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="danger" onClick={this.props.close}>
            Close
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
