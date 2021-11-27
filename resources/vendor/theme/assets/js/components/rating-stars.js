// Rating Stars
const ratingStars = () => {
  rateStars = (event) => {
    const starsContainer = event.target.closest(".rating-stars");
    const stars = Array.from(starsContainer.children);
    const totalStars = stars.length;
    const index = stars.indexOf(event.target);
    let count = 0;
    count = totalStars - index;
    stars.forEach((star) => star.classList.remove("active"));

    event.target.classList.add("active");

    console.log("You have rated " + count + " stars.");
  };

  on("body", "click", ".rating-stars", (event) => {
    rateStars(event);
  });
};

ratingStars();
