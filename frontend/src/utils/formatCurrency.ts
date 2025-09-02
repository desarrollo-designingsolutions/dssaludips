export const formatCurrency = (value: any) => {
  // Parse to string
  const str = String(value)
  // Allow number only
  const onlyNumber = str.replace(/[^0-9]/g, "");
  // Format separately by thousands
  const formatToCurrency = onlyNumber.replace(/\B(?=(\d{3})+(?!\d))/g, ".")
  return formatToCurrency
}

export const formatToCurrencyFormat = (value:any) => {
  return String(value).replaceAll('.', ',');
}
export const formatToPorcentage = (value:any) => {
  return String(value).replaceAll('.', ',');
}

export const currencyFormat = (value: any) => {
  let val = String(value).replaceAll(/[^0-9,-]/g, "");

  const parts = val.split(",");

  if (parts.length > 2) {
    val = parts[0] + "," + parts.slice(1).join("");
  }

  let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  let decimalPart = parts[1] ? parts[1].slice(0, 2) : "00";

  let valueFormatted = decimalPart
    ? `${integerPart},${decimalPart}`
    : integerPart;

  return valueFormatted;
}
 