/**
 * Format utilities for displaying data
 */

export const formatRating = (rating) => {
  if (rating === null || rating === undefined || rating === '') return 'N/A';
  const numRating = Number(rating);
  return isNaN(numRating) ? 'N/A' : numRating.toFixed(1);
};

export const formatPercentage = (value) => {
  if (value === null || value === undefined || value === '') return 'N/A';
  const numValue = Number(value);
  return isNaN(numValue) ? 'N/A' : `${numValue.toFixed(1)}%`;
};

export const formatNumber = (value, decimals = 0) => {
  if (value === null || value === undefined || value === '') return 'N/A';
  const numValue = Number(value);
  return isNaN(numValue) ? 'N/A' : numValue.toFixed(decimals);
};