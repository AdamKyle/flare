import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import ReactDatatable from '@ashvin27/react-datatable';
import CardTemplate from '../game/components/templates/card-template';
import MarketHistory from './market-history';

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
        key: "address",
        text: "Address",
        sortable: true
      },
      {
        key: "postcode",
        text: "Postcode",
        sortable: true
      },
      {
        key: "rating",
        text: "Rating",
        sortable: true
      },
      {
        key: "type_of_food",
        text: "Type of Food"
      }
    ];

    this.config = {
      page_size: 10,
      length_menu: [10, 20, 50],
      show_filter: true,
      show_pagination: true,
      pagination: 'advance',
      button: {
        excel: false,
        print: false
      }
    }
    this.state = {
      records: [
        {
          id: 3,
          name: 'Sample',
          address: '1234 test',
          postcode: '1234',
          rating: 5,
          type_of_food: 'sample food',
        }
      ],
    }
  }

  rowClickedHandler(event, data, rowIndex) {
    console.log("event", event);
    console.log("row data", data);
    console.log("row index", rowIndex);
  }

  typeChange(type) {
    console.log(type);
  }

  render() {
    return (
      <CardTemplate
        OtherCss="p-3"
        cardTitle="Market Board"
        customButtonType="drop-down"
        buttonTitle="Types"
        buttons={[
          'weapons',
          'armour',
          'artifacts',
          'spells',
        ]}
        onChange={this.typeChange.bind(this)}
      >
        <MarketHistory />

        <ReactDatatable
          config={this.config}
          records={this.state.records}
          columns={this.columns}
          onRowClicked={this.rowClickedHandler}
        />
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