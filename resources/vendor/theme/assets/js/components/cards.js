// Cards
const cards = () => {
  // Toggle Card Selection
  const toggleCardSelection = (event) => {
    const card = event.target.closest(".card");
    card.classList.toggle("card_selected");
  };

  on("body", "click", '[data-toggle="cardSelection"]', (event) => {
    toggleCardSelection(event);
  });

  // Toggle Row Selection
  const toggleRowSelection = (event) => {
    const row = event.target.closest("tr");
    row.classList.toggle("row_selected");
  };

  on("body", "click", '[data-toggle="rowSelection"]', (event) => {
    toggleRowSelection(event);
  });
};

cards();
