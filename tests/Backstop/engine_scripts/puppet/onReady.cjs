module.exports = async (page, scenario, vp) => {
  console.log('SCENARIO > ' + scenario.label);
  await require('./clickAndHoverHelper.cjs')(page, scenario);

  await page.waitForFunction(() => document.fonts.ready);

  // add more ready handlers here...
};
