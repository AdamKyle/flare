import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import ReactDatatable from '@ashvin27/react-datatable';
import {Alert} from 'react-bootstrap';
import Card from '../../game/components/templates/card';
import ItemName from "../components/item-name";
import SellItemModal from "./sell-item-modal";

export default class CharacterItems extends Component {
  constructor(props) {
    super(props);

    this.columns = [
      {
        key: "name",
        text: "Name",
        sortable: false,
        cell: row => <div data-tag="allowRowEvents">
          <div>
            <ItemName item={this.getItem(row.id)}/>
          </div>
        </div>,
      },
      {
        key: "type",
        text: "Item Type",
        sortable: false
      },
      {
        key: "cost",
        text: "Item Cost",
        sortable: true,
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</div>
        </div>,
      },
    ];

    this.config = {
      page_size: 10,
      length_menu: [10, 25, 50, 100],
      show_filter: true,
      show_pagination: true,
      pagination: 'advance',
    }

    this.state = {
      records: [],
      showModal: false,
      modalData: {},
      is404: false,
      showSuccess: false,
    }
  }

  componentDidMount() {
    axios.get('/api/market-board/character-items/' + this.props.characterId)
      .then((result) => {
        this.setState({
          records: result.data.slots,
        });
      }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return window.location = '/game';
        }
      }
    });
  }

  rowClickedHandler(event, data, rowIndex) {
    axios.get('/api/market-board/item', {
      params: {
        item_id: data.item_id
      }
    }).then((result) => {
      let resultData = result.data;

      resultData.slot_id = data.id;

      this.setState({
        modalData: result.data,
        showModal: true,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        if (err.status === 401) {
          return location.reload();
        }

        if (err.status === 429) {
          return window.location = '/game';
        }
      }
    })
  }

  getItem(id) {
    return this.state.records.filter((r) => r.id === id)[0].item;
  }

  closeModal(refresh) {
    this.setState({
      modalData: {},
      showModal: false,
      showSuccess: true,
    }, () => {
      this.typeChange('reset');
    });
  }

  closeSuccess() {
    this.setState({
      showSuccess: false,
    });
  }

  itemListedMessage(message, messageType) {
    this.setState({
      message: message,
      messageType: messageType,
    });
  }

  typeChange(type) {
    let params = {};

    if (type !== 'reset') {
      params = {
        params: {
          type: type
        }
      };

      this.setState({
        type: type,
      });
    } else {
      this.setState({
        type: null,
      });
    }


    axios.get('/api/market-board/character-items/' + this.props.characterId, params).then((result) => {
      this.setState({
        records: result.data.slots,
      });
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return window.location = '/game';
        }
      }
    });
  }

  render() {
    return (
      <Card
        OtherCss="p-3"
        cardTitle="Sellable Items"
        showButton={true}
        customButtonType="drop-down"
        buttonTitle="Types"
        buttons={[
          {type: 'reset', name: 'Reset Filter'},
          {type: 'weapon', name: 'Weapon'},
          {type: 'body', name: 'Body'},
          {type: 'shield', name: 'Shield'},
          {type: 'feet', name: 'Feet'},
          {type: 'leggings', name: 'Leggings'},
          {type: 'sleeves', name: 'Sleeves'},
          {type: 'helmet', name: 'Helmet'},
          {type: 'gloves', name: 'Gloves'},
          {type: 'spell-damage', name: 'Spell Damage'},
          {type: 'spell-healing', name: 'Spell Healing'},
          {type: 'ring', name: 'Ring'},
          {type: 'artifact', name: 'Artifact'},
        ]}
        onChange={this.typeChange.bind(this)}
      >
        {
          this.state.showSuccess ?
            <Alert variant="success" onClose={this.closeSuccess.bind(this)} dismissible>
              Your item was listed on the market board!
            </Alert>
          : null
        }
        {
          this.state.is404 ?
            <div className="alert alert-danger mb-2 mt-2">
              Item Not found
            </div>
            : null
        }
        <div className="alert alert-info mb-3 mt-3">
          <p><strong>Click on rows, to open the sale window</strong></p>
          Cost is relative to the cost of the attached affixes, along with base item price. Use this as a guide only when selling items. Opening the individual items
          will show you a chart, which indicates the latest sales price for the item in question.<br />
          The chart in the item dialogue, represents the sale price for this item and its associated affixes only.
        </div>
        <ReactDatatable
          config={this.config}
          records={this.state.records}
          columns={this.columns}
          onRowClicked={this.rowClickedHandler.bind(this)}
        />

        {
          this.state.showModal ?
            <SellItemModal showModal={this.state.showModal} closeModal={this.closeModal.bind(this)}
                           modalData={this.state.modalData} characterId={this.props.characterId} /> : null
        }
      </Card>
    );
  }
}

const characterItems = document.getElementById('sell-items-on-market');
const character = document.head.querySelector('meta[name="character"]');

if (characterItems !== null) {
  ReactDOM.render(
    <CharacterItems characterId={character.content}/>,
    characterItems
  );
}
