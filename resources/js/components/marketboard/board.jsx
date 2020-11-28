import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import ReactDatatable from '@ashvin27/react-datatable';
import CardTemplate from '../game/components/templates/card-template';
import MarketHistory from './market-history';
import PurchaseModal from './purchase-modal';

class Board extends Component {
  constructor(props) {
    super(props);
    this.columns = [
      {
        key: "name",
        text: "Name",
        sortable: true
      },
      {
        key: "type",
        text: "Item Type",
        sortable: true
      },
      {
        key: "character_name",
        text: "Listed By",
        sortable: true
      },
      {
        key: "listed_price",
        text: "Listed For",
        sortable: true
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
    }
  }

  componentDidMount() {
    axios.get('/api/market-board/items').then((result) => {
      this.setState({
        records: result.data
      });
    }).catch((error) => {
      console.error(error);
    });
  }

  rowClickedHandler(event, data, rowIndex) {
    this.setState({
      modalData: data,
      showModal: true,
    });
  }

  closeModal() {
    this.setState({
      modalData: {},
      showModal: false,
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
    }

    axios.get('/api/market-board/items', params).then((result) => {
      this.setState({
        records: result.data,
      });
    }).catch((error) => {
      console.error(error);
    });
  }

  render() {
    if (_.isEmpty(this.state.records)) {
      return null;
    }

    return (
      <CardTemplate
        OtherCss="p-3"
        cardTitle="Market Board"
        customButtonType="drop-down"
        buttonTitle="Types"
        buttons={[
          { type:'reset', name: 'Reset Filter'},
          { type:'weapon', name: 'Weapon'},
          { type:'body', name: 'Body'},
          { type:'shield', name: 'Shield'},
          { type:'feet', name: 'Feet'},
          { type:'leggings', name: 'Leggings'},
          { type:'sleeves', name: 'Sleeves'},
          { type:'helmet', name: 'Helmet'},
          { type:'gloves', name: 'Gloves'},
          { type:'spell-damage', name: 'Spell Damage'},
          { type:'spell-healing', name: 'Spell Healing'},
          { type:'ring', name: 'Ring'},
          { type:'artifact', name: 'Artifact'},
        ]}
        onChange={this.typeChange.bind(this)}
      >
        <MarketHistory />

        <ReactDatatable
          config={this.config}
          records={this.state.records}
          columns={this.columns}
          onRowClicked={this.rowClickedHandler.bind(this)}
        />

        {this.state.showModal ? 
          <PurchaseModal 
            closeModal={this.closeModal.bind(this)}
            showModal={this.state.showModal}
            modalData={this.state.modalData}
          /> : null
        }
      </CardTemplate>
    );
  }
}

const marketBoard = document.getElementById('market');
const character = document.head.querySelector('meta[name="character"]');

if (marketBoard !== null) {
  ReactDOM.render(
    <Board characterId={character.content} />,
    marketBoard
  );
}