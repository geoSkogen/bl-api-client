var call_now_box = document.querySelector('#call_now')
var locale_no_box = document.querySelector('#locale_no')
var directory_box = document.querySelector('#directory')

var index_els = [call_now_box, locale_no_box, directory_box]

var app = {
  'call_now' : function (el) {
    el.value = !el.value
    //console.log(el.value)
  },
  'locale_no' : function (el) {
    var xy = document.querySelector('#xy')
    var dir = xy.value.split(',')[1]
    var loc = (Number(el.value)-1).toString()
    xy.value = loc + ',' + dir
    //console.log(xy.value)
  },
  'directory' : function (el) {
    var xy = document.querySelector('#xy')
    var loc = xy.value.split(',')[0]
    xy.value =  loc + ',' + el.value
    //console.log(xy.value)
  }
}

index_els.forEach( function (el) {
  el.addEventListener('change', function () {
    app[el.id](this)
  })
})
