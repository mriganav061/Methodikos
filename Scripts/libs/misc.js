/**
 * misc.js
 *
 * This file used for misc functions
 *
 */
 
// Used for div show/hide
function ToggleDisplay(divId) {
  var div = document.getElementById(divId);
  div.style.display = (div.style.display=="block" ? "none" : "block");
}