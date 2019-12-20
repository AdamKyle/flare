import React from 'react';
import Shop from './shop';

export default class Sidebar extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {

    return (
      <div className="card">
        <div className="card-body">
          <Shop characterId={this.props.characterId} />
        </div>
      </div>
    );
  }
}
