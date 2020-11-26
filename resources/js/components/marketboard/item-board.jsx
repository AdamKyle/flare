import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import ReactDatatable from '@ashvin27/react-datatable';

class ItemBoard extends Component {
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

  render() {
    return (
      <ReactDatatable
        config={this.config}
        records={this.state.records}
        columns={this.columns}
      />
    );
  }
}

const itemBoard = document.getElementById('market-info');
const character = document.head.querySelector('meta[name="character"]');

if (itemBoard !== null) {
  ReactDOM.render(
    <ItemBoard characterId={character.content} item={document.querySelector('#market-info').getAttribute('item-id')} />,
    itemBoard
  );
}