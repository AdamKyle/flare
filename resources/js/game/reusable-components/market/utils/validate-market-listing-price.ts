import MarketListingPriceValidation from '../types/market-listing-price-validation';

export const validateMarketListingPrice = (
  priceInput: string
): MarketListingPriceValidation => {
  if (priceInput.trim() === '') {
    return { price: null, error: 'Enter a listing price.' };
  }

  const parsedPrice = Number(priceInput);

  if (!Number.isFinite(parsedPrice) || !Number.isInteger(parsedPrice)) {
    return {
      price: null,
      error: 'The listing price must be a whole number of Gold.',
    };
  }

  if (parsedPrice < 1) {
    return { price: null, error: 'The listing price must be at least 1 Gold.' };
  }

  return { price: parsedPrice, error: null };
};
