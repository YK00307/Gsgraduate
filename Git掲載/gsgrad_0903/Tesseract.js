async function extractImage(file) {
  const result = await Tesseract.recognize(file, 'eng');
  return result.data.text;
}
