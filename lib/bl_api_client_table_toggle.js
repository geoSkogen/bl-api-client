'use strict'

var table_toggle_button = document.querySelector('#bl-reviews-table-toggle')
var table_to_toggle = document.querySelector('#bl-reviews-upload-preview')
var content_toggle = {
  'none' : 'show',
  'block' : 'hide'
}

table_toggle_button.addEventListener('click',function () {
  var toggle_arr = table_to_toggle.getAttribute('data-disp').split(',')
  var text_to_toggle = document.querySelector('#data-text')
  var toggle_text = content_toggle[toggle_arr[0]]
  table_to_toggle.style.display = toggle_arr[0]
  text_to_toggle.innerHTML = toggle_text
  toggle_arr.reverse()
  table_to_toggle.setAttribute('data-disp',toggle_arr.join(','))
})
