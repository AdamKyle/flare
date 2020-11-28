import React from 'react';
import { Modal, Button } from 'react-bootstrap';
import ItemDetails from './item-details';

export default class PurchaseModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      item: null,
      loading: true,
    }
  }

  componentDidMount() {
    axios.get('/api/market-board/'+this.props.modalData.item_id+'/listing-details')
         .then((result) => {
           this.setState({
             item: result.data,
             loading: false,
           })
         }).catch((error) => {
           console.error(error);
         });
  }

  handleClose() {
    this.props.closeModal();
  }

  render() {
    return (
      <Modal 
        show={this.props.showModal} 
        onHide={this.props.closeModal} 
        backdrop="static"
        keyboard={false}
        size="lg"
      >
        <Modal.Header closeButton>
          <Modal.Title>{this.props.modalData.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>Is this the item you would like to purchase? It will <strong>cost</strong>: {this.props.modalData.listed_price * 1.05} Gold (incl. 5% tax)</p>
          { this.state.loading ? 'Loading please wait ...' : <ItemDetails item={this.state.item} /> }
        </Modal.Body>
        <Modal.Footer>
          <Button variant="danger" onClick={this.props.closeModal}>
            Close
          </Button>
          <Button variant="primary" onClick={this.props.closeModal}>
            Purchase
          </Button>
        </Modal.Footer>
      </Modal>
    );
  }
}