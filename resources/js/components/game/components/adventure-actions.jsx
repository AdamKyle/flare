import React from 'react';

export default class AdeventureActions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      adventureDetails: [],
      isLoading: true,
    }
  }

  componentDidMount() {
    this.setState({
      adventureDetails: this.props.adventureDetails,
      isLoading:   false,
    });
  }

  componentDidUpdate(prevProps) {
    if (this.props !== prevProps) {
      this.setState({
        adventureDetails: this.props.adventureDetails,
      });
    }
  }

  hideAdventure() {
    this.props.openAdventureDetails(false);
  }

  adventures() {
    const details = [];

    _.forEach(this.state.adventureDetails, (adventure) => {
        details.push(
            <div className="row" key={adventure.id}>
                <div class="col-md-2">{adventure.name}</div>
                <div class="col-md-10">
                    <a href="" className="mr-2 btn btn-primary">Embark</a>
                    <a href={'/adeventures/' + adventure.id} target="_blank" className="mr-2 btn btn-primary">Details</a>
                </div>
            </div>
        );
    });

    return details;
  }

  render() {
    if (this.state.isLoading) {
      return <>Please wait ...</>
    }

    return (
      <div className="card">
		<div className="card-body p-3">
          <div className="clearfix">
            <h4 className="card-title float-left">Adventures</h4>
            <button className="float-right btn btn-sm btn-danger" onClick={this.hideAdventure.bind(this)}>Close</button>
          </div>
          <hr />
          <div className="alert alert-info">You may only embark on one adventure at a time</div>
		  {this.adventures()}
        </div>
      </div>
    )
  }
} 